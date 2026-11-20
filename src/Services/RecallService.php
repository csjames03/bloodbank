<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Domain\Actor;
use App\Domain\DomainException;
use App\Domain\RecallOutcome;
use App\Repositories\ActivityRepository;
use App\Repositories\DonorRepository;

/** Logging calls made to donors during a recall drive. */
final class RecallService
{
    private readonly Database $db;
    private readonly DonorRepository $donors;
    private readonly ActivityRepository $activity;

    public function __construct()
    {
        $this->db = Database::instance();
        $this->donors = new DonorRepository($this->db);
        $this->activity = new ActivityRepository($this->db);
    }

    /** @return array<string, mixed> the donor */
    public function logContact(int $donorId, RecallOutcome $outcome, string $note, Actor $by): array
    {
        if (!$by->isAdmin()) {
            throw new DomainException('Only blood bank staff can log donor calls.');
        }

        return $this->db->transaction(function () use ($donorId, $outcome, $note, $by): array {
            $donor = $this->donors->find($donorId) ?? throw new DomainException('Donor not found.');
            if (!$this->donors->isEligible($donorId)) {
                throw new DomainException("{$donor['name']} is not eligible to donate right now, so they shouldn't be called.");
            }

            $this->donors->logContact($donorId, $outcome, $note, $donor['blood_type'], $by->name);
            $this->activity->log('donor', $donorId, 'contacted', $by, sprintf(
                '%s (%s): %s%s',
                $donor['name'],
                $donor['blood_type'],
                $outcome->label(),
                $note !== '' ? " — {$note}" : '',
            ));

            return $donor;
        });
    }
}
