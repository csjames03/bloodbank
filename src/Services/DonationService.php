<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Domain\Actor;
use App\Domain\BloodGroup;
use App\Domain\DomainException;
use App\Repositories\ActivityRepository;
use App\Repositories\BloodTypeRepository;
use App\Repositories\DonationRepository;

/**
 * Recording, correcting and removing donations while keeping the
 * blood inventory consistent.
 *
 * A donor is one person: a returning donor (same name and phone number) is
 * matched to their existing record instead of being duplicated, must be on
 * file with the same blood group, and must wait the minimum interval
 * between donations.
 */
final class DonationService
{
    private readonly Database $db;
    private readonly DonationRepository $donations;
    private readonly BloodTypeRepository $bloodTypes;
    private readonly Notifier $notify;
    private readonly ActivityRepository $activity;

    public function __construct()
    {
        $this->db = Database::instance();
        $this->donations = new DonationRepository($this->db);
        $this->bloodTypes = new BloodTypeRepository($this->db);
        $this->notify = new Notifier($this->db);
        $this->activity = new ActivityRepository($this->db);
    }

    /**
     * Record a donation. Walk-ins logged by a hospital are attributed to it.
     * A returning donor (same name and phone) is matched to their record.
     *
     * @param array<string, mixed> $donor
     * @return array{id: int, returning: bool, donations: int}
     */
    public function record(array $donor, BloodGroup $group, int $bags, Actor $by): array
    {
        return $this->db->transaction(function () use ($donor, $group, $bags, $by): array {
            $typeId = $this->typeId($group);
            $existing = $this->donations->findReturningDonor($donor['first_name'], $donor['last_name'], $donor['contact']);

            if ($existing !== null) {
                $this->checkReturningDonor($existing, $group);
                $donorId = (int) $existing['id'];
                $this->donations->updateDonor($donorId, $donor, $typeId); // refresh age, address, medical notes
            } else {
                $donorId = $this->donations->createDonor($donor, $typeId);
            }

            $id = $this->donations->create($donorId, $typeId, $bags, $by->hospitalId());
            $this->bloodTypes->adjust($typeId, $bags);

            $name = trim($donor['first_name'] . ' ' . $donor['last_name']);
            $note = sprintf('%s, %s of %s', $name, plural($bags, 'bag'), $group->value);
            $this->activity->log('donation', $id, 'recorded', $by, $note . ($existing !== null ? ' (returning donor)' : ''));

            if ($by->isHospital()) {
                $this->notify->donationLogged($by->id, $name, $group->value, $bags);
            }

            return [
                'id'        => $id,
                'returning' => $existing !== null,
                'donations' => $existing !== null ? (int) $existing['donations'] + 1 : 1,
            ];
        });
    }

    /** Earliest date the donor may give again after a donation on `$lastDonatedAt`. */
    public static function nextEligibleDate(string $lastDonatedAt): \DateTimeImmutable
    {
        $days = (int) config('inventory.donation_interval_days', 56);

        return (new \DateTimeImmutable($lastDonatedAt))->modify("+{$days} days");
    }

    /** @param array<string, mixed> $existing */
    private function checkReturningDonor(array $existing, BloodGroup $group): void
    {
        if ($existing['blood_type'] !== $group->value) {
            throw new DomainException(sprintf(
                '%s is on record as %s. If the lab has re-grouped this donor, correct their existing donation first.',
                $existing['name'],
                $existing['blood_type'],
            ), 'blood_type');
        }

        if ($existing['last_donated_at'] !== null) {
            $eligible = self::nextEligibleDate($existing['last_donated_at']);
            if ($eligible > new \DateTimeImmutable()) {
                throw new DomainException(sprintf(
                    '%s last donated on %s. Donors must wait %d days between donations, so they can give again from %s.',
                    $existing['name'],
                    fmt_date($existing['last_donated_at']),
                    (int) config('inventory.donation_interval_days', 56),
                    $eligible->format('M j, Y'),
                ), 'contact');
            }
        }
    }

    /** @param array<string, mixed> $donor */
    public function update(int $id, array $donor, BloodGroup $group, int $bags, Actor $by): void
    {
        $this->db->transaction(function () use ($id, $donor, $group, $bags, $by): void {
            $donation = $this->lock($id);
            $oldTypeId = (int) $donation['blood_type_id'];
            $newTypeId = $this->typeId($group);

            $this->guardDispatched(function () use ($oldTypeId, $newTypeId, $donation, $bags): void {
                if ($oldTypeId === $newTypeId) {
                    $this->reduce($newTypeId, $bags - (int) $donation['bags']);
                } else {
                    $this->reduce($oldTypeId, -(int) $donation['bags']);
                    $this->bloodTypes->adjust($newTypeId, $bags);
                }
            });

            $this->donations->updateDonor((int) $donation['donor_id'], $donor, $newTypeId);
            $this->donations->update($id, $newTypeId, $bags);

            $changes = [];
            if ($oldTypeId !== $newTypeId) {
                $changes[] = 'group ' . ($this->bloodTypes->find($oldTypeId)['code'] ?? '?') . ' → ' . $group->value;
            }
            if ((int) $donation['bags'] !== $bags) {
                $changes[] = "bags {$donation['bags']} → {$bags}";
            }
            $this->activity->log('donation', $id, 'updated', $by, $changes === [] ? 'Donor details' : implode(', ', $changes));
        });
    }

    public function delete(int $id, Actor $by): void
    {
        $this->db->transaction(function () use ($id, $by): void {
            $donation = $this->lock($id);

            $this->guardDispatched(fn () => $this->reduce(
                (int) $donation['blood_type_id'],
                -(int) $donation['bags'],
            ));

            $this->donations->delete($id);
            $this->activity->log('donation', $id, 'deleted', $by, sprintf(
                '%s of %s removed from stock',
                plural((int) $donation['bags'], 'bag'),
                $this->bloodTypes->find((int) $donation['blood_type_id'])['code'] ?? '?',
            ));
            $this->donations->deleteDonorIfOrphaned((int) $donation['donor_id']);
        });
    }

    /** Apply a stock change and alert staff if it leaves the group low or empty. */
    private function reduce(int $typeId, int $delta): void
    {
        $left = $this->bloodTypes->adjust($typeId, $delta);
        if ($delta < 0) {
            $this->notify->stockReduced($typeId, $left, $delta);
        }
    }

    /** @return array<string, mixed> */
    private function lock(int $id): array
    {
        return $this->db->one('SELECT * FROM donations WHERE id = ? FOR UPDATE', [$id])
            ?? throw new DomainException('Donation not found.');
    }

    private function typeId(BloodGroup $group): int
    {
        return (int) ($this->bloodTypes->findByCode($group->value)['id']
            ?? throw new DomainException('Unknown blood type.'));
    }

    /**
     * Removing bags from stock fails when they have already been sent to
     * hospitals — explain that instead of the generic shortage message.
     */
    private function guardDispatched(callable $change): void
    {
        try {
            $change();
        } catch (DomainException) {
            throw new DomainException(
                'Those bags have already been dispatched to hospitals, so this donation can no longer be reduced, moved or removed.'
            );
        }
    }
}
