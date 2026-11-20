<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * The eight blood groups and their stock. Stock is never stored: it is the
 * number of available, unexpired units, counted live from blood_units.
 */
final class BloodTypeRepository extends Repository
{
    /** Display order used everywhere in the UI. */
    private const ORDER = "array_position(ARRAY['A+','A-','B+','B-','AB+','AB-','O+','O-']::varchar[], bt.code)";

    /** Usable stock per group, as a correlated count. */
    private const BAGS = "(SELECT COUNT(*) FROM blood_units u
                           WHERE u.blood_type_id = bt.id AND u.status = 'available' AND u.expires_at > now())";

    /** @return list<array{id: int, code: string, bags: int}> */
    public function all(): array
    {
        return array_map(
            static fn (array $r) => ['id' => (int) $r['id'], 'code' => $r['code'], 'bags' => (int) $r['bags']],
            $this->db->all('SELECT bt.id, bt.code, ' . self::BAGS . ' AS bags FROM blood_types bt ORDER BY ' . self::ORDER),
        );
    }

    /** @return array<string, int> code => available bags */
    public function stockByCode(): array
    {
        return array_column($this->all(), 'bags', 'code');
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->db->one('SELECT bt.id, bt.code, ' . self::BAGS . ' AS bags FROM blood_types bt WHERE bt.id = ?', [$id]);
    }

    /** @return array<string, mixed>|null */
    public function findByCode(string $code): ?array
    {
        return $this->db->one('SELECT bt.id, bt.code, ' . self::BAGS . ' AS bags FROM blood_types bt WHERE bt.code = ?', [$code]);
    }

    public function available(int $bloodTypeId): int
    {
        return (int) ($this->find($bloodTypeId)['bags'] ?? 0);
    }

    /**
     * Units issued to hospitals per group over the last `$days` days
     * (recalled units don't count — they came back).
     *
     * @return array<string, int> code => units
     */
    public function issuedSince(int $days): array
    {
        return array_map('intval', array_column($this->db->all(
            "SELECT bt.code, COUNT(u.id) AS n
             FROM blood_types bt
             LEFT JOIN blood_units u ON u.blood_type_id = bt.id AND u.status = 'dispatched'
                  AND u.dispatched_at >= now() - make_interval(days => ?)
                  -- now() is the transaction start, so a dispatch being made right
                  -- now doesn't inflate the rate it is measured against.
                  AND u.dispatched_at < now()
             GROUP BY bt.code",
            [$days],
        ), 'n', 'code'));
    }

    /**
     * Earliest expiry among available units, per group.
     *
     * @return array<string, string> code => timestamp
     */
    public function earliestExpiry(): array
    {
        return array_column($this->db->all(
            "SELECT bt.code, MIN(u.expires_at) AS expires_at
             FROM blood_units u JOIN blood_types bt ON bt.id = u.blood_type_id
             WHERE u.status = 'available' AND u.expires_at > now()
             GROUP BY bt.code"
        ), 'expires_at', 'code');
    }

    /**
     * Full inventory picture per blood group.
     *
     * @return list<array<string, mixed>>
     */
    public function inventory(): array
    {
        $warn = (int) config('inventory.expiry_warning_days', 7);

        return $this->db->all(
            "SELECT bt.id, bt.code,
                    COALESCE(u.available, 0)  AS bags,
                    COALESCE(u.expiring, 0)   AS expiring,
                    COALESCE(u.quarantine, 0) AS quarantine,
                    COALESCE(u.wasted, 0)     AS wasted,
                    u.next_expiry,
                    COALESCE(d.donated, 0)    AS donated,
                    COALESCE(d.donations, 0)  AS donations,
                    COALESCE(x.dispatched, 0) AS dispatched,
                    COALESCE(r.pending, 0)    AS pending
             FROM blood_types bt
             LEFT JOIN (
                 SELECT blood_type_id,
                        COUNT(*) FILTER (WHERE status = 'available' AND expires_at > now()) AS available,
                        COUNT(*) FILTER (WHERE status = 'available' AND expires_at > now()
                                           AND expires_at <= now() + make_interval(days => :warn)) AS expiring,
                        COUNT(*) FILTER (WHERE status = 'quarantine' AND expires_at > now()) AS quarantine,
                        COUNT(*) FILTER (WHERE status IN ('expired', 'discarded')
                                           AND COALESCE(discarded_at, expires_at) >= now() - INTERVAL '30 days') AS wasted,
                        MIN(expires_at) FILTER (WHERE status = 'available' AND expires_at > now()) AS next_expiry
                 FROM blood_units GROUP BY blood_type_id
             ) u ON u.blood_type_id = bt.id
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
             ORDER BY " . self::ORDER,
            ['warn' => $warn],
        );
    }
}
