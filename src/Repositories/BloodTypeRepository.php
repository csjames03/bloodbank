<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\DomainException;

final class BloodTypeRepository extends Repository
{
    /** Display order used everywhere in the UI. */
    private const ORDER = "array_position(ARRAY['A+','A-','B+','B-','AB+','AB-','O+','O-']::varchar[], bt.code)";

    /** @return list<array{id: int, code: string, bags: int}> */
    public function all(): array
    {
        return $this->db->all('SELECT bt.id, bt.code, bt.bags FROM blood_types bt ORDER BY ' . self::ORDER);
    }

    /** @return array<string, int> code => available bags */
    public function stockByCode(): array
    {
        return array_column($this->all(), 'bags', 'code');
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->db->one('SELECT id, code, bags FROM blood_types WHERE id = ?', [$id]);
    }

    /** @return array<string, mixed>|null */
    public function findByCode(string $code): ?array
    {
        return $this->db->one('SELECT id, code, bags FROM blood_types WHERE code = ?', [$code]);
    }

    /**
     * Full inventory picture per blood group.
     *
     * @return list<array<string, mixed>>
     */
    public function inventory(): array
    {
        return $this->db->all(
            "SELECT bt.id, bt.code, bt.bags,
                    COALESCE(d.donated, 0)    AS donated,
                    COALESCE(d.donations, 0)  AS donations,
                    COALESCE(x.dispatched, 0) AS dispatched,
                    COALESCE(r.pending, 0)    AS pending
             FROM blood_types bt
             LEFT JOIN (
                 SELECT blood_type_id, SUM(bags) AS donated, COUNT(*) AS donations
                 FROM donations GROUP BY blood_type_id
             ) d ON d.blood_type_id = bt.id
             LEFT JOIN (
                 -- What actually left the building, by the group that was sent.
                 SELECT a.blood_type_id, SUM(a.bags) AS dispatched
                 FROM request_allocations a
                 JOIN blood_requests rq ON rq.id = a.request_id
                 WHERE rq.status IN ('in_transit', 'completed')
                 GROUP BY a.blood_type_id
             ) x ON x.blood_type_id = bt.id
             LEFT JOIN (
                 -- Outstanding demand, by the patient's group.
                 SELECT blood_type_id, SUM(bags) AS pending
                 FROM blood_requests WHERE status = 'pending' GROUP BY blood_type_id
             ) r ON r.blood_type_id = bt.id
             ORDER BY " . self::ORDER
        );
    }

    /**
     * Atomically change available stock. Throws if it would go below zero.
     * Must be called inside a transaction when combined with other writes.
     */
    public function adjust(int $bloodTypeId, int $delta): int
    {
        $bags = $this->db->value(
            'UPDATE blood_types SET bags = bags + :delta
             WHERE id = :id AND bags + :delta2 >= 0
             RETURNING bags',
            ['delta' => $delta, 'delta2' => $delta, 'id' => $bloodTypeId],
        );

        if ($bags === null) {
            $row = $this->db->one('SELECT code, bags FROM blood_types WHERE id = ?', [$bloodTypeId]);
            throw new DomainException(sprintf(
                'Not enough %s in stock — only %s available.',
                $row['code'] ?? 'blood',
                plural((int) ($row['bags'] ?? 0), 'bag'),
            ));
        }

        return (int) $bags;
    }
}
