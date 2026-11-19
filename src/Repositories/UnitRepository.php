<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Paginator;

/**
 * Physical blood bags (units). All stock movements happen here.
 */
final class UnitRepository extends Repository
{
    private const SELECT = "
        SELECT u.id, u.unit_code, u.status, u.collected_at, u.expires_at, u.screened_at,
               u.dispatched_at, u.discarded_at, u.discard_reason, u.request_id, u.donation_id,
               bt.code AS blood_type, u.blood_type_id,
               h.name AS hospital_name
        FROM blood_units u
        JOIN blood_types bt ON bt.id = u.blood_type_id
        LEFT JOIN blood_requests r ON r.id = u.request_id
        LEFT JOIN hospitals h ON h.id = r.hospital_id";

    /** Create `$count` quarantined units for a donation; expiry follows the shelf life. */
    public function createForDonation(int $donationId, int $bloodTypeId, int $count): void
    {
        $days = (int) config('inventory.shelf_life_days', 35);
        $this->db->run(
            "INSERT INTO blood_units (donation_id, blood_type_id, collected_at, expires_at)
             SELECT dn.id, :type, dn.donated_at, dn.donated_at + make_interval(days => :days)
             FROM donations dn CROSS JOIN generate_series(1, :count)
             WHERE dn.id = :donation",
            ['type' => $bloodTypeId, 'days' => $days, 'count' => $count, 'donation' => $donationId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function forDonation(int $donationId, bool $lock = false): array
    {
        return $this->db->all(
            'SELECT * FROM blood_units WHERE donation_id = ? ORDER BY id' . ($lock ? ' FOR UPDATE' : ''),
            [$donationId],
        );
    }

    /** @return array<string, mixed>|null */
    public function lock(int $id): ?array
    {
        return $this->db->one('SELECT * FROM blood_units WHERE id = ? FOR UPDATE', [$id]);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->db->one(self::SELECT . ' WHERE u.id = ?', [$id]);
    }

    public function deleteForDonation(int $donationId): void
    {
        $this->db->run('DELETE FROM blood_units WHERE donation_id = ?', [$donationId]);
    }

    /** Move a donation's quarantined units to another group (lab re-grouping). */
    public function regroup(int $donationId, int $bloodTypeId): void
    {
        $this->db->run(
            "UPDATE blood_units SET blood_type_id = ? WHERE donation_id = ? AND status = 'quarantine'",
            [$bloodTypeId, $donationId],
        );
    }

    /**
     * Release a donation's quarantined units into stock. Units that expired
     * while waiting are marked expired instead.
     *
     * @return array{released: int, expired: int}
     */
    public function release(int $donationId): array
    {
        $rows = $this->db->all(
            "UPDATE blood_units
             SET status = CASE WHEN expires_at > now() THEN 'available' ELSE 'expired' END,
                 screened_at = now()
             WHERE donation_id = ? AND status = 'quarantine'
             RETURNING status",
            [$donationId],
        );
        $statuses = array_column($rows, 'status');

        return [
            'released' => count(array_keys($statuses, 'available', true)),
            'expired'  => count(array_keys($statuses, 'expired', true)),
        ];
    }

    /** Discard every quarantined unit of a donation (failed screening). */
    public function discardQuarantined(int $donationId, string $reason): int
    {
        return $this->db->run(
            "UPDATE blood_units SET status = 'discarded', screened_at = now(), discarded_at = now(), discard_reason = ?
             WHERE donation_id = ? AND status = 'quarantine'",
            [mb_substr($reason, 0, 160), $donationId],
        )->rowCount();
    }

    public function discard(int $id, string $reason): void
    {
        $this->db->run(
            "UPDATE blood_units SET status = 'discarded', discarded_at = now(), discard_reason = ? WHERE id = ?",
            [mb_substr($reason, 0, 160), $id],
        );
    }

    /**
     * Lock the `$count` usable units of a group that expire first (FEFO).
     * Rows locked by a concurrent dispatch are skipped, never double-issued.
     *
     * @return list<int> unit ids (may be fewer than `$count`)
     */
    public function pickFefo(int $bloodTypeId, int $count): array
    {
        return array_map('intval', array_column($this->db->all(
            "SELECT id FROM blood_units
             WHERE blood_type_id = ? AND status = 'available' AND expires_at > now()
             ORDER BY expires_at, id
             LIMIT ? FOR UPDATE SKIP LOCKED",
            [$bloodTypeId, $count],
        ), 'id'));
    }

    /** @param list<int> $ids */
    public function dispatch(array $ids, int $requestId): void
    {
        if ($ids === []) {
            return;
        }
        $this->db->run(
            "UPDATE blood_units SET status = 'dispatched', request_id = ?, dispatched_at = now()
             WHERE id = ANY (?::int[])",
            [$requestId, '{' . implode(',', $ids) . '}'],
        );
    }

    /**
     * Return a recalled request's units to stock (or mark them expired if
     * they ran out of shelf life while out).
     *
     * @return list<array{blood_type_id: int, status: string}>
     */
    public function recall(int $requestId): array
    {
        return array_map(
            static fn (array $r) => ['blood_type_id' => (int) $r['blood_type_id'], 'status' => $r['status']],
            $this->db->all(
                "UPDATE blood_units
                 SET status = CASE WHEN expires_at > now() THEN 'available' ELSE 'expired' END,
                     request_id = NULL, dispatched_at = NULL
                 WHERE request_id = ? AND status = 'dispatched'
                 RETURNING blood_type_id, status",
                [$requestId],
            ),
        );
    }

    /**
     * Units sent for each request, soonest expiry first.
     *
     * @param list<int> $requestIds
     * @return array<int, list<array{unit_code: string, blood_type: string, expires_at: string}>>
     */
    public function forRequests(array $requestIds): array
    {
        if ($requestIds === []) {
            return [];
        }

        $map = [];
        foreach ($this->db->all(
            "SELECT u.request_id, u.unit_code, bt.code AS blood_type, u.expires_at
             FROM blood_units u JOIN blood_types bt ON bt.id = u.blood_type_id
             WHERE u.request_id = ANY (?::int[]) ORDER BY u.expires_at, u.id",
            ['{' . implode(',', array_map('intval', $requestIds)) . '}'],
        ) as $u) {
            $map[(int) $u['request_id']][] = ['unit_code' => $u['unit_code'], 'blood_type' => $u['blood_type'], 'expires_at' => $u['expires_at']];
        }

        return $map;
    }

    /**
     * Mark every quarantined or available unit past its expiry as expired.
     *
     * @return list<array{blood_type_id: int, code: string, was: string}>
     */
    public function expireDue(): array
    {
        return array_map(
            static fn (array $r) => ['blood_type_id' => (int) $r['blood_type_id'], 'code' => $r['code'], 'was' => $r['was']],
            $this->db->all(
                "WITH due AS (
                     SELECT id, status AS was FROM blood_units
                     WHERE status IN ('quarantine', 'available') AND expires_at <= now()
                     FOR UPDATE SKIP LOCKED
                 )
                 UPDATE blood_units u SET status = 'expired'
                 FROM due, blood_types bt
                 WHERE u.id = due.id AND bt.id = u.blood_type_id
                 RETURNING u.blood_type_id, bt.code, due.was",
            ),
        );
    }

    /**
     * Flag available units entering the expiry-warning window (once each).
     *
     * @return list<array{code: string}>
     */
    public function flagExpiringSoon(int $days): array
    {
        return $this->db->all(
            "UPDATE blood_units u SET expiry_warned_at = now()
             FROM blood_types bt
             WHERE bt.id = u.blood_type_id AND u.status = 'available' AND u.expiry_warned_at IS NULL
               AND u.expires_at > now() AND u.expires_at <= now() + make_interval(days => ?)
             RETURNING bt.code",
            [$days],
        );
    }

    /**
     * Donations with units still in quarantine, oldest first.
     *
     * @return list<array<string, mixed>>
     */
    public function screeningQueue(): array
    {
        return $this->db->all(
            "SELECT dn.id AS donation_id, dn.donated_at, dn.bags, bt.code AS blood_type,
                    concat_ws(' ', d.first_name, d.middle_name, d.last_name) AS donor_name,
                    d.sex, d.age, d.medical_status, h.name AS hospital_name,
                    string_agg(u.unit_code, ', ' ORDER BY u.id) AS unit_codes,
                    MIN(u.expires_at) AS expires_at, COUNT(u.id) AS units
             FROM blood_units u
             JOIN donations dn ON dn.id = u.donation_id
             JOIN donors d ON d.id = dn.donor_id
             JOIN blood_types bt ON bt.id = u.blood_type_id
             LEFT JOIN hospitals h ON h.id = dn.hospital_id
             WHERE u.status = 'quarantine'
             GROUP BY dn.id, bt.code, d.id, h.name
             ORDER BY dn.donated_at, dn.id"
        );
    }

    public function quarantinedDonations(): int
    {
        return (int) $this->db->value("SELECT COUNT(DISTINCT donation_id) FROM blood_units WHERE status = 'quarantine'");
    }

    /**
     * Available units expiring within `$days`, soonest first.
     *
     * @return list<array<string, mixed>>
     */
    public function expiringSoon(int $days, int $limit = 8): array
    {
        return $this->db->all(
            self::SELECT . " WHERE u.status = 'available' AND u.expires_at > now()
               AND u.expires_at <= now() + make_interval(days => ?)
             ORDER BY u.expires_at, u.id LIMIT ?",
            [$days, $limit],
        );
    }

    /** @param array{q?: ?string, status?: ?string, type?: ?string} $filters */
    public function paginate(array $filters, int $page, int $perPage): Paginator
    {
        $clauses = [];
        $params = [];
        $warn = (int) config('inventory.expiry_warning_days', 7);

        $status = $filters['status'] ?? null;
        if ($status === 'expiring') {
            $clauses[] = "u.status = 'available' AND u.expires_at > now() AND u.expires_at <= now() + make_interval(days => {$warn})";
        } elseif ($status === 'available') {
            $clauses[] = "u.status = 'available' AND u.expires_at > now()";
        } elseif (!empty($status)) {
            $clauses[] = 'u.status = :status';
            $params['status'] = $status;
        }
        if (!empty($filters['type'])) {
            $clauses[] = 'bt.code = :type';
            $params['type'] = $filters['type'];
        }
        if (!empty($filters['q'])) {
            $clauses[] = 'u.unit_code ILIKE :q';
            $params['q'] = $this->like($filters['q']);
        }
        $where = $clauses === [] ? '' : 'WHERE ' . implode(' AND ', $clauses);

        $total = (int) $this->db->value(
            "SELECT COUNT(*) FROM blood_units u JOIN blood_types bt ON bt.id = u.blood_type_id {$where}",
            $params,
        );
        $items = $this->db->all(
            self::SELECT . " {$where}
             ORDER BY CASE u.status WHEN 'available' THEN 0 WHEN 'quarantine' THEN 1 WHEN 'dispatched' THEN 2 ELSE 3 END,
                      CASE WHEN u.status IN ('available', 'quarantine') THEN u.expires_at END ASC,
                      u.id DESC
             LIMIT :limit OFFSET :offset",
            [...$params, 'limit' => $perPage, 'offset' => Paginator::offset($page, $perPage)],
        );

        return new Paginator($items, $total, $page, $perPage);
    }

    /** @return array<string, int> status (plus "expiring") => count */
    public function statusCounts(): array
    {
        $warn = (int) config('inventory.expiry_warning_days', 7);
        $row = $this->db->one(
            "SELECT COUNT(*) AS all,
                    COUNT(*) FILTER (WHERE status = 'available' AND expires_at > now()) AS available,
                    COUNT(*) FILTER (WHERE status = 'available' AND expires_at > now()
                                       AND expires_at <= now() + make_interval(days => ?)) AS expiring,
                    COUNT(*) FILTER (WHERE status = 'quarantine') AS quarantine,
                    COUNT(*) FILTER (WHERE status = 'dispatched') AS dispatched,
                    COUNT(*) FILTER (WHERE status = 'discarded') AS discarded,
                    COUNT(*) FILTER (WHERE status = 'expired') AS expired
             FROM blood_units",
            [$warn],
        );

        return array_map('intval', $row ?? []);
    }
}
