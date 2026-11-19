<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Paginator;
use App\Domain\Priority;
use App\Domain\RequestStatus;

final class RequestRepository extends Repository
{
    private const SELECT = '
        SELECT r.id, r.doctor_name, r.bags, r.priority, r.needed_by, r.status, r.requested_at, r.approved_at,
               r.completed_at, r.cancelled_at, r.hospital_id, r.blood_type_id,
               bt.code AS blood_type, h.name AS hospital_name, h.address AS hospital_address
        FROM blood_requests r
        JOIN blood_types bt ON bt.id = r.blood_type_id
        JOIN hospitals h    ON h.id = r.hospital_id';

    /**
     * Triage order. Pending requests first, by priority, then the earliest
     * "needed by", then oldest first (first come, first served). In-transit
     * next; closed requests last, newest first.
     */
    private const ORDER = "
        ORDER BY CASE r.status WHEN 'pending' THEN 0 WHEN 'in_transit' THEN 1 ELSE 2 END,
                 CASE WHEN r.status = 'pending'
                      THEN CASE r.priority WHEN 'emergency' THEN 0 WHEN 'urgent' THEN 1 ELSE 2 END
                      ELSE 0 END,
                 CASE WHEN r.status = 'pending' THEN r.needed_by END ASC NULLS LAST,
                 CASE WHEN r.status = 'pending' THEN r.requested_at END ASC,
                 r.requested_at DESC, r.id DESC";

    /** @param array{q?: ?string, type?: ?string, status?: ?string, hospital_id?: ?int} $filters */
    public function paginate(array $filters, int $page, int $perPage): Paginator
    {
        [$where, $params] = $this->filters($filters);

        $total = (int) $this->db->value(
            'SELECT COUNT(*) FROM blood_requests r JOIN blood_types bt ON bt.id = r.blood_type_id
             JOIN hospitals h ON h.id = r.hospital_id ' . $where,
            $params,
        );

        $items = $this->db->all(
            self::SELECT . " {$where} " . self::ORDER . ' LIMIT :limit OFFSET :offset',
            [...$params, 'limit' => $perPage, 'offset' => Paginator::offset($page, $perPage)],
        );

        return new Paginator($this->attach($items), $total, $page, $perPage);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $row = $this->db->one(self::SELECT . ' WHERE r.id = ?', [$id]);

        return $row === null ? null : $this->attach([$row])[0];
    }

    /**
     * Row-locks the request for the rest of the transaction so two admins
     * can't approve/cancel the same request at the same time.
     *
     * @return array<string, mixed>|null
     */
    public function lock(int $id): ?array
    {
        return $this->db->one('SELECT * FROM blood_requests WHERE id = ? FOR UPDATE', [$id]);
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $limit = 6, ?int $hospitalId = null): array
    {
        $rows = $hospitalId === null
            ? $this->db->all(self::SELECT . self::ORDER . ' LIMIT ?', [$limit])
            : $this->db->all(self::SELECT . ' WHERE r.hospital_id = ?' . self::ORDER . ' LIMIT ?', [$hospitalId, $limit]);

        return $this->attach($rows);
    }

    public function create(int $hospitalId, int $bloodTypeId, string $doctor, int $bags, Priority $priority, ?\DateTimeImmutable $neededBy): int
    {
        return (int) $this->db->value(
            'INSERT INTO blood_requests (hospital_id, blood_type_id, doctor_name, bags, priority, needed_by)
             VALUES (?, ?, ?, ?, ?, ?) RETURNING id',
            [$hospitalId, $bloodTypeId, $doctor, $bags, $priority->value, $neededBy?->format(DATE_ATOM)],
        );
    }

    /** @param array<int, int> $allocation blood_type_id => bags */
    public function saveAllocation(int $requestId, array $allocation): void
    {
        $this->db->run('DELETE FROM request_allocations WHERE request_id = ?', [$requestId]);
        foreach ($allocation as $typeId => $bags) {
            $this->db->run(
                'INSERT INTO request_allocations (request_id, blood_type_id, bags) VALUES (?, ?, ?)',
                [$requestId, $typeId, $bags],
            );
        }
    }

    /** @return list<array{blood_type_id: int, code: string, bags: int}> */
    public function allocation(int $requestId): array
    {
        return array_map(
            static fn (array $a) => ['blood_type_id' => (int) $a['blood_type_id'], 'code' => $a['code'], 'bags' => (int) $a['bags']],
            $this->db->all(
                'SELECT a.blood_type_id, bt.code, a.bags FROM request_allocations a
                 JOIN blood_types bt ON bt.id = a.blood_type_id
                 WHERE a.request_id = ? ORDER BY a.bags DESC, bt.code',
                [$requestId],
            ),
        );
    }

    /**
     * Add `allocation` (code => bags sent), `sent` (total), `units` (bags sent, by code) and `actors`
     * (action => actor name, from the audit log) to each request row.
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function attach(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $ids = array_map(static fn (array $r) => (int) $r['id'], $rows);
        $list = '{' . implode(',', $ids) . '}';

        $allocations = [];
        foreach ($this->db->all(
            'SELECT a.request_id, bt.code, a.bags FROM request_allocations a
             JOIN blood_types bt ON bt.id = a.blood_type_id
             WHERE a.request_id = ANY (?::int[]) ORDER BY a.bags DESC, bt.code',
            [$list],
        ) as $a) {
            $allocations[(int) $a['request_id']][$a['code']] = (int) $a['bags'];
        }

        $actors = (new ActivityRepository($this->db))->actorsFor('request', $ids);
        $units = (new UnitRepository($this->db))->forRequests($ids);

        return array_map(static function (array $r) use ($allocations, $actors, $units): array {
            $id = (int) $r['id'];
            $r['allocation'] = $allocations[$id] ?? [];
            $r['sent'] = array_sum($r['allocation']);
            $r['actors'] = $actors[$id] ?? [];
            $r['units'] = $units[$id] ?? [];

            return $r;
        }, $rows);
    }

    public function setStatus(int $id, RequestStatus $status): void
    {
        $timestamp = match ($status) {
            RequestStatus::InTransit => 'approved_at',
            RequestStatus::Completed => 'completed_at',
            RequestStatus::Cancelled => 'cancelled_at',
            RequestStatus::Pending => null,
        };

        $this->db->run(
            'UPDATE blood_requests SET status = ?' . ($timestamp ? ", {$timestamp} = now()" : '') . ' WHERE id = ?',
            [$status->value, $id],
        );
    }

    /**
     * Number of requests and bags per status.
     *
     * @return array<string, array{count: int, bags: int}>
     */
    public function summary(?int $hospitalId = null): array
    {
        $rows = $this->db->all(
            'SELECT status, COUNT(*) AS count, COALESCE(SUM(bags), 0) AS bags FROM blood_requests'
            . ($hospitalId ? ' WHERE hospital_id = ?' : '') . ' GROUP BY status',
            $hospitalId ? [$hospitalId] : [],
        );

        $summary = [];
        foreach (RequestStatus::cases() as $status) {
            $summary[$status->value] = ['count' => 0, 'bags' => 0];
        }
        foreach ($rows as $row) {
            $summary[$row['status']] = ['count' => (int) $row['count'], 'bags' => (int) $row['bags']];
        }

        return $summary;
    }

    /**
     * @param array{q?: ?string, type?: ?string, status?: ?string, hospital_id?: ?int} $filters
     * @return array{string, array<string, mixed>}
     */
    private function filters(array $filters): array
    {
        $clauses = [];
        $params = [];

        if (!empty($filters['q'])) {
            $clauses[] = '(r.doctor_name ILIKE :q OR h.name ILIKE :q2)';
            $params['q'] = $params['q2'] = $this->like($filters['q']);
        }
        if (!empty($filters['type'])) {
            $clauses[] = 'bt.code = :type';
            $params['type'] = $filters['type'];
        }
        if (!empty($filters['status'])) {
            $clauses[] = 'r.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['hospital_id'])) {
            $clauses[] = 'r.hospital_id = :hospital_id';
            $params['hospital_id'] = $filters['hospital_id'];
        }

        return [$clauses === [] ? '' : 'WHERE ' . implode(' AND ', $clauses), $params];
    }
}
