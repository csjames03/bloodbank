<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Paginator;
use App\Domain\RecallOutcome;

/**
 * Donor recall: who can be asked to give right now, and who has been asked.
 *
 * A donor is eligible when they are not deferred and their last donation is
 * at least `donation_interval_days` ago.
 */
final class DonorRepository extends Repository
{
    /** Eligibility filter shared by the list and the counts. Binds :interval. */
    private const ELIGIBLE = "
        NOT d.deferred_permanently
        AND (d.deferred_until IS NULL OR d.deferred_until <= now())
        AND COALESCE((SELECT MAX(donated_at) FROM donations WHERE donor_id = d.id), '-infinity')
            <= now() - make_interval(days => :interval)";

    /** @return array<string, int> code => eligible donors */
    public function eligibleCounts(): array
    {
        return array_map('intval', array_column($this->db->all(
            'SELECT bt.code, COUNT(d.id) AS n
             FROM blood_types bt
             LEFT JOIN donors d ON d.blood_type_id = bt.id AND ' . self::ELIGIBLE . '
             GROUP BY bt.code',
            ['interval' => $this->interval()],
        ), 'n', 'code'));
    }

    /**
     * Eligible donors of one group. Not-recently-contacted first, then the
     * most loyal (most donations), then the longest rested.
     */
    public function eligible(string $code, int $page, int $perPage): Paginator
    {
        $params = ['code' => $code, 'interval' => $this->interval()];
        $where = 'WHERE bt.code = :code AND ' . self::ELIGIBLE;

        $total = (int) $this->db->value(
            "SELECT COUNT(*) FROM donors d JOIN blood_types bt ON bt.id = d.blood_type_id {$where}",
            $params,
        );
        $items = $this->db->all(
            "SELECT d.id, concat_ws(' ', d.first_name, d.middle_name, d.last_name) AS name,
                    d.first_name, d.last_name, d.sex, d.age, d.contact, d.address, bt.code AS blood_type,
                    (SELECT MAX(donated_at) FROM donations WHERE donor_id = d.id) AS last_donated_at,
                    (SELECT COUNT(*) FROM donations WHERE donor_id = d.id) AS donations,
                    c.outcome AS last_outcome, c.contacted_at AS last_contacted_at, c.contacted_by AS last_contacted_by,
                    (c.contacted_at IS NOT NULL AND c.contacted_at > now() - make_interval(days => :cooldown)) AS recently_contacted
             FROM donors d
             JOIN blood_types bt ON bt.id = d.blood_type_id
             LEFT JOIN LATERAL (
                 SELECT outcome, contacted_at, contacted_by FROM donor_contacts
                 WHERE donor_id = d.id ORDER BY contacted_at DESC, id DESC LIMIT 1
             ) c ON true
             {$where}
             ORDER BY recently_contacted, donations DESC, last_donated_at ASC NULLS FIRST, d.id
             LIMIT :limit OFFSET :offset",
            [
                ...$params,
                'cooldown' => (int) config('supply.recall_cooldown_days', 7),
                'limit'    => $perPage,
                'offset'   => Paginator::offset($page, $perPage),
            ],
        );

        return new Paginator($items, $total, $page, $perPage);
    }

    /**
     * Every eligible donor of a group, for export (call lists, SMS blasts).
     *
     * @return list<array<string, mixed>>
     */
    public function eligibleForExport(string $code): array
    {
        return $this->eligible($code, 1, 10000)->items;
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->db->one(
            "SELECT d.id, concat_ws(' ', d.first_name, d.last_name) AS name, bt.code AS blood_type,
                    d.deferred_permanently, d.deferred_until,
                    (SELECT MAX(donated_at) FROM donations WHERE donor_id = d.id) AS last_donated_at
             FROM donors d JOIN blood_types bt ON bt.id = d.blood_type_id WHERE d.id = ?",
            [$id],
        );
    }

    public function isEligible(int $donorId): bool
    {
        return (bool) $this->db->value(
            'SELECT 1 FROM donors d WHERE d.id = :id AND ' . self::ELIGIBLE,
            ['id' => $donorId, 'interval' => $this->interval()],
        );
    }

    public function logContact(int $donorId, RecallOutcome $outcome, string $note, string $bloodType, string $by): void
    {
        $this->db->run(
            'INSERT INTO donor_contacts (donor_id, outcome, note, blood_type, contacted_by) VALUES (?, ?, ?, ?, ?)',
            [$donorId, $outcome->value, mb_substr($note, 0, 160), $bloodType, $by],
        );
    }

    /** @return array<string, int> outcome => count, over the last `$days` days for a group */
    public function contactSummary(string $code, int $days): array
    {
        return array_map('intval', array_column($this->db->all(
            'SELECT outcome, COUNT(*) AS n FROM donor_contacts
             WHERE blood_type = ? AND contacted_at > now() - make_interval(days => ?)
             GROUP BY outcome',
            [$code, $days],
        ), 'n', 'outcome'));
    }

    private function interval(): int
    {
        return (int) config('inventory.donation_interval_days', 56);
    }
}
