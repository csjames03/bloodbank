<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Domain\Actor;
use App\Domain\BloodGroup;
use App\Domain\DomainException;
use App\Domain\ScreeningFailure;
use App\Repositories\ActivityRepository;
use App\Repositories\BloodTypeRepository;
use App\Repositories\DonationRepository;
use App\Repositories\UnitRepository;

/**
 * Lab screening. Every donation is quarantined until it is tested for
 * transfusion-transmissible infections and its blood group is confirmed.
 * Only then do its units count as stock.
 */
final class ScreeningService
{
    private readonly Database $db;
    private readonly UnitRepository $units;
    private readonly DonationRepository $donations;
    private readonly BloodTypeRepository $bloodTypes;
    private readonly ActivityRepository $activity;

    public function __construct()
    {
        $this->db = Database::instance();
        $this->units = new UnitRepository($this->db);
        $this->donations = new DonationRepository($this->db);
        $this->bloodTypes = new BloodTypeRepository($this->db);
        $this->activity = new ActivityRepository($this->db);
    }

    /**
     * Screening passed. `$confirmed` is the group the lab confirmed; if it
     * differs from the one recorded, the donation, its units and the donor
     * are re-grouped before release.
     *
     * @return array{released: int, expired: int, regrouped: ?string}
     */
    public function pass(int $donationId, BloodGroup $confirmed, Actor $by): array
    {
        $this->requireStaff($by);

        return $this->db->transaction(function () use ($donationId, $confirmed, $by): array {
            $donation = $this->lockQuarantined($donationId);
            $regrouped = null;

            if ($donation['blood_type'] !== $confirmed->value) {
                $typeId = (int) $this->bloodTypes->findByCode($confirmed->value)['id'];
                $this->db->run('UPDATE donations SET blood_type_id = ? WHERE id = ?', [$typeId, $donationId]);
                $this->db->run('UPDATE donors SET blood_type_id = ?, updated_at = now() WHERE id = ?', [$typeId, $donation['donor_id']]);
                $this->units->regroup($donationId, $typeId);
                $regrouped = "{$donation['blood_type']} → {$confirmed->value}";
            }

            $result = $this->units->release($donationId);
            $this->activity->log('donation', $donationId, 'screened', $by, sprintf(
                'Passed: %s released to stock%s%s',
                plural($result['released'], 'unit'),
                $regrouped ? "; re-grouped {$regrouped}" : '',
                $result['expired'] ? "; {$result['expired']} expired while waiting" : '',
            ));

            return [...$result, 'regrouped' => $regrouped];
        });
    }

    /**
     * Screening failed: every unit is discarded and the donor is deferred
     * (permanently for HIV and hepatitis).
     */
    public function fail(int $donationId, ScreeningFailure $failure, Actor $by): int
    {
        $this->requireStaff($by);

        return $this->db->transaction(function () use ($donationId, $failure, $by): int {
            $donation = $this->lockQuarantined($donationId);
            $count = $this->units->discardQuarantined($donationId, 'Failed screening: ' . $failure->label());

            $days = $failure->deferralDays();
            if ($failure->isPermanentDeferral() || $days !== null) {
                $this->donations->deferDonor(
                    (int) $donation['donor_id'],
                    $failure->label(),
                    $failure->isPermanentDeferral(),
                    $days !== null ? new \DateTimeImmutable("+{$days} days") : null,
                );
            }

            $this->activity->log('donation', $donationId, 'screened', $by, sprintf(
                'Failed: %s; %s discarded%s',
                $failure->label(),
                plural($count, 'unit'),
                $failure->isPermanentDeferral() ? '; donor permanently deferred' : ($days !== null ? "; donor deferred {$days} days" : ''),
            ));

            return $count;
        });
    }

    /** @return array<string, mixed> */
    private function lockQuarantined(int $donationId): array
    {
        $donation = $this->db->one(
            'SELECT dn.id, dn.donor_id, bt.code AS blood_type FROM donations dn
             JOIN blood_types bt ON bt.id = dn.blood_type_id WHERE dn.id = ? FOR UPDATE OF dn',
            [$donationId],
        ) ?? throw new DomainException('Donation not found.');

        $units = $this->units->forDonation($donationId, lock: true);
        if (!in_array('quarantine', array_column($units, 'status'), true)) {
            throw new DomainException('This donation has already been screened.');
        }

        return $donation;
    }

    private function requireStaff(Actor $by): void
    {
        if (!$by->isAdmin()) {
            throw new DomainException('Only blood bank staff can record screening results.');
        }
    }
}
