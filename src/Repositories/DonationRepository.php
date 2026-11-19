<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Paginator;

/**
 * Donors and their donations (collection events).
 */
final class DonationRepository extends Repository
{
    private const SELECT = "
        SELECT dn.id, dn.bags, dn.donated_at, dn.donor_id, dn.hospital_id,
               bt.code AS blood_type, bt.id AS blood_type_id,
               d.first_name, d.middle_name, d.last_name, d.sex, d.age,
               d.contact, d.address, d.medical_status,
               concat_ws(' ', d.first_name, d.middle_name, d.last_name) AS donor_name,
               h.name AS hospital_name,
               (SELECT COUNT(*) FROM blood_units WHERE donation_id = dn.id AND status = 'quarantine') AS quarantined,
               (SELECT COUNT(*) FROM blood_units WHERE donation_id = dn.id AND status <> 'quarantine') AS screened,
               (SELECT string_agg(DISTINCT status, ',') FROM blood_units WHERE donation_id = dn.id) AS unit_statuses
        FROM donations dn
        JOIN donors d      ON d.id = dn.donor_id
        JOIN blood_types bt ON bt.id = dn.blood_type_id
        LEFT JOIN hospitals h ON h.id = dn.hospital_id";

    /** @param array{q?: ?string, type?: ?string} $filters */
    public function paginate(array $filters, int $page, int $perPage): Paginator
    {
        [$where, $params] = $this->filters($filters);

        $total = (int) $this->db->value(
            'SELECT COUNT(*) FROM donations dn JOIN donors d ON d.id = dn.donor_id
             JOIN blood_types bt ON bt.id = dn.blood_type_id ' . $where,
            $params,
        );

        $items = $this->db->all(
            self::SELECT . " {$where} ORDER BY dn.donated_at DESC, dn.id DESC LIMIT :limit OFFSET :offset",
            [...$params, 'limit' => $perPage, 'offset' => Paginator::offset($page, $perPage)],
        );

        return new Paginator($items, $total, $page, $perPage);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->db->one(self::SELECT . ' WHERE dn.id = ?', [$id]);
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $limit = 6): array
    {
        return $this->db->all(self::SELECT . ' ORDER BY dn.donated_at DESC, dn.id DESC LIMIT ?', [$limit]);
    }

    /** @return array{total_bags: int, month_bags: int, donors: int} */
    public function stats(): array
    {
        $row = $this->db->one(
            "SELECT COALESCE(SUM(bags), 0) AS total_bags,
                    COALESCE(SUM(bags) FILTER (WHERE donated_at >= date_trunc('month', now())), 0) AS month_bags,
                    COUNT(DISTINCT donor_id) AS donors
             FROM donations"
        );

        return array_map('intval', $row ?? []);
    }

    /**
     * An existing donor with the same first and last name and phone number,
     * ignoring case, spacing and phone punctuation, plus their latest donation.
     *
     * @return array<string, mixed>|null
     */
    public function findReturningDonor(string $firstName, string $lastName, string $contact): ?array
    {
        return $this->db->one(
            "SELECT d.id, d.blood_type_id, bt.code AS blood_type,
                    d.deferred_until, d.deferred_permanently, d.deferral_reason,
                    concat_ws(' ', d.first_name, d.last_name) AS name,
                    (SELECT MAX(donated_at) FROM donations WHERE donor_id = d.id) AS last_donated_at,
                    (SELECT COUNT(*) FROM donations WHERE donor_id = d.id) AS donations
             FROM donors d JOIN blood_types bt ON bt.id = d.blood_type_id
             WHERE lower(regexp_replace(d.first_name, '\\s+', ' ', 'g')) = lower(regexp_replace(?, '\\s+', ' ', 'g'))
               AND lower(regexp_replace(d.last_name, '\\s+', ' ', 'g'))  = lower(regexp_replace(?, '\\s+', ' ', 'g'))
               AND regexp_replace(d.contact, '\\D', '', 'g') = regexp_replace(?, '\\D', '', 'g')
             ORDER BY d.id LIMIT 1",
            [trim($firstName), trim($lastName), $contact],
        );
    }

    /** Stop a donor giving blood again, permanently or until a date. */
    public function deferDonor(int $donorId, string $reason, bool $permanent, ?\DateTimeImmutable $until): void
    {
        $this->db->run(
            'UPDATE donors SET deferred_permanently = deferred_permanently OR ?, deferral_reason = ?,
                    deferred_until = GREATEST(deferred_until, ?::timestamptz), updated_at = now()
             WHERE id = ?',
            [$permanent ? 'true' : 'false', $reason, $until?->format(DATE_ATOM), $donorId],
        );
    }

    /** @param array<string, mixed> $donor */
    public function createDonor(array $donor, int $bloodTypeId): int
    {
        return (int) $this->db->value(
            'INSERT INTO donors (first_name, middle_name, last_name, sex, age, contact, address, blood_type_id, medical_status)
             VALUES (:first_name, :middle_name, :last_name, :sex, :age, :contact, :address, :blood_type_id, :medical_status)
             RETURNING id',
            [...$this->donorParams($donor), 'blood_type_id' => $bloodTypeId],
        );
    }

    /** @param array<string, mixed> $donor */
    public function updateDonor(int $donorId, array $donor, int $bloodTypeId): void
    {
        $this->db->run(
            'UPDATE donors SET first_name = :first_name, middle_name = :middle_name, last_name = :last_name,
                    sex = :sex, age = :age, contact = :contact, address = :address,
                    blood_type_id = :blood_type_id, medical_status = :medical_status, updated_at = now()
             WHERE id = :id',
            [...$this->donorParams($donor), 'blood_type_id' => $bloodTypeId, 'id' => $donorId],
        );
    }

    public function create(int $donorId, int $bloodTypeId, int $bags, ?int $hospitalId): int
    {
        return (int) $this->db->value(
            'INSERT INTO donations (donor_id, blood_type_id, bags, hospital_id) VALUES (?, ?, ?, ?) RETURNING id',
            [$donorId, $bloodTypeId, $bags, $hospitalId],
        );
    }

    public function update(int $id, int $bloodTypeId, int $bags): void
    {
        $this->db->run('UPDATE donations SET blood_type_id = ?, bags = ? WHERE id = ?', [$bloodTypeId, $bags, $id]);
    }

    public function delete(int $id): void
    {
        $this->db->run('DELETE FROM donations WHERE id = ?', [$id]);
    }

    /** Remove a donor once they no longer have any donation on record. */
    public function deleteDonorIfOrphaned(int $donorId): void
    {
        $this->db->run(
            'DELETE FROM donors WHERE id = ? AND NOT EXISTS (SELECT 1 FROM donations WHERE donor_id = donors.id)',
            [$donorId],
        );
    }

    /**
     * @param array{q?: ?string, type?: ?string} $filters
     * @return array{string, array<string, mixed>}
     */
    private function filters(array $filters): array
    {
        $clauses = [];
        $params = [];

        if (!empty($filters['q'])) {
            $clauses[] = "(concat_ws(' ', d.first_name, d.middle_name, d.last_name) ILIKE :q
                           OR concat_ws(' ', d.first_name, d.last_name) ILIKE :q2
                           OR d.contact ILIKE :q3)";
            $params['q'] = $params['q2'] = $params['q3'] = $this->like($filters['q']);
        }
        if (!empty($filters['type'])) {
            $clauses[] = 'bt.code = :type';
            $params['type'] = $filters['type'];
        }

        return [$clauses === [] ? '' : 'WHERE ' . implode(' AND ', $clauses), $params];
    }

    /**
     * @param array<string, mixed> $donor
     * @return array<string, mixed>
     */
    private function donorParams(array $donor): array
    {
        return [
            'first_name'     => $donor['first_name'],
            'middle_name'    => $donor['middle_name'] ?: null,
            'last_name'      => $donor['last_name'],
            'sex'            => $donor['sex'],
            'age'            => $donor['age'],
            'contact'        => $donor['contact'],
            'address'        => $donor['address'],
            'medical_status' => $donor['medical_status'] ?: 'Fit to donate',
        ];
    }
}
