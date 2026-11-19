<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Domain\Actor;
use App\Domain\BloodGroup;
use App\Domain\DomainException;
use App\Domain\Priority;
use App\Domain\RequestStatus;
use App\Repositories\ActivityRepository;
use App\Repositories\BloodTypeRepository;
use App\Repositories\RequestRepository;

/**
 * Business rules for hospital blood requests.
 *
 *  - Requests are always accepted, whatever the stock. A shortage is the
 *    blood bank's problem to solve at dispatch, not a reason to lose demand.
 *  - At approval, staff decide what to send: the patient's group, compatible
 *    substitutes, or fewer bags than asked (a partial fill).
 *  - The receiving hospital confirms receipt. Staff may record it on their behalf.
 *  - Every state change runs in a transaction with the request row locked,
 *    and is written to the audit log with the actor.
 */
final class RequestService
{
    private readonly Database $db;
    private readonly RequestRepository $requests;
    private readonly BloodTypeRepository $bloodTypes;
    private readonly ActivityRepository $activity;
    private readonly Notifier $notify;

    public function __construct()
    {
        $this->db = Database::instance();
        $this->requests = new RequestRepository($this->db);
        $this->bloodTypes = new BloodTypeRepository($this->db);
        $this->activity = new ActivityRepository($this->db);
        $this->notify = new Notifier($this->db);
    }

    public function create(
        int $hospitalId,
        BloodGroup $group,
        string $doctor,
        int $bags,
        Priority $priority,
        ?\DateTimeImmutable $neededBy,
        Actor $by,
    ): int {
        if ($by->isHospital() && $by->id !== $hospitalId) {
            throw new DomainException('Hospitals can only request blood for themselves.');
        }

        $type = $this->bloodTypes->findByCode($group->value)
            ?? throw new DomainException('Unknown blood type.', 'blood_type');

        $doctor = preg_match('/^dr\.?\s/i', $doctor) ? $doctor : 'Dr. ' . $doctor;

        return $this->db->transaction(function () use ($hospitalId, $type, $doctor, $bags, $priority, $neededBy, $by): int {
            $id = $this->requests->create($hospitalId, (int) $type['id'], $doctor, $bags, $priority, $neededBy);
            $this->activity->log('request', $id, 'created', $by, $priority === Priority::Routine ? '' : $priority->label());
            $this->notify->requestCreated($id, filedByStaff: $by->isAdmin());

            return $id;
        });
    }

    /**
     * The allocation staff would most likely choose, from current stock.
     *
     * @param array<string, mixed> $request a row from RequestRepository
     * @param array<string, int>   $stock   code => bags
     * @return array<string, int>
     */
    public static function suggest(array $request, array $stock): array
    {
        return BloodGroup::from($request['blood_type'])->suggestAllocation((int) $request['bags'], $stock);
    }

    /**
     * Approve and dispatch. `$allocation` (code => bags) says what to send;
     * when empty, the suggested allocation from current stock is used.
     *
     * @param array<string, int> $allocation
     * @return array{sent: int, allocation: array<string, int>}
     */
    public function approve(int $id, array $allocation, Actor $by): array
    {
        $this->requireStaff($by);

        return $this->db->transaction(function () use ($id, $allocation, $by): array {
            $request = $this->lockOpen($id, RequestStatus::InTransit);
            $row = $this->requests->find($id) ?? throw new DomainException('Request not found.');
            $patient = BloodGroup::from($row['blood_type']);

            $allocation = array_filter($allocation, static fn (int $n) => $n > 0);
            if ($allocation === []) {
                $allocation = self::suggest($row, $this->bloodTypes->stockByCode());
                if ($allocation === []) {
                    throw new DomainException("No {$patient->value}-compatible blood is in stock right now.");
                }
            }

            $this->validateAllocation($patient, (int) $request['bags'], $allocation);

            // Deduct in a fixed order so concurrent approvals can't deadlock.
            ksort($allocation);
            $byTypeId = [];
            foreach ($allocation as $code => $bags) {
                $typeId = (int) $this->bloodTypes->findByCode($code)['id'];
                $left = $this->bloodTypes->adjust($typeId, -$bags);
                $this->notify->stockReduced($typeId, $left, -$bags);
                $byTypeId[$typeId] = $bags;
            }

            $this->requests->saveAllocation($id, $byTypeId);
            $this->requests->setStatus($id, RequestStatus::InTransit);

            $sent = array_sum($allocation);
            $note = 'Sent ' . self::describe($allocation)
                . ($sent < (int) $request['bags'] ? " (partial: {$sent} of {$request['bags']})" : '');
            $this->activity->log('request', $id, 'approved', $by, $note);
            $this->notify->requestDispatched($id, $allocation);

            return ['sent' => $sent, 'allocation' => $allocation];
        });
    }

    /** Confirm the blood arrived. Hospitals confirm their own; staff may record it for them. */
    public function receive(int $id, Actor $by): void
    {
        $this->db->transaction(function () use ($id, $by): void {
            $request = $this->lockOpen($id, RequestStatus::Completed);
            if ($by->isHospital() && (int) $request['hospital_id'] !== $by->id) {
                throw new DomainException('Request not found.');
            }

            $this->requests->setStatus($id, RequestStatus::Completed);
            $this->activity->log('request', $id, 'received', $by, $by->isAdmin() ? 'Recorded by staff on behalf of the hospital' : '');
            $this->notify->requestReceived($id, confirmedByHospital: $by->isHospital());
        });
    }

    /**
     * Cancel a request. Hospitals may only cancel their own pending requests;
     * staff may also cancel in-transit ones, which returns every allocated
     * bag to the group it came from.
     */
    public function cancel(int $id, Actor $by): void
    {
        $this->db->transaction(function () use ($id, $by): void {
            $request = $this->lockOpen($id, RequestStatus::Cancelled);
            $status = RequestStatus::from($request['status']);

            if ($by->isHospital()) {
                if ((int) $request['hospital_id'] !== $by->id) {
                    throw new DomainException('Request not found.');
                }
                if ($status !== RequestStatus::Pending) {
                    throw new DomainException('This request has already been dispatched. Please contact the blood bank to cancel it.');
                }
            }

            if ($status === RequestStatus::InTransit) {
                foreach ($this->requests->allocation($id) as $a) {
                    $this->bloodTypes->adjust($a['blood_type_id'], $a['bags']);
                }
            }

            $this->requests->setStatus($id, RequestStatus::Cancelled);
            $this->activity->log('request', $id, 'cancelled', $by, $status === RequestStatus::InTransit ? 'Dispatch recalled, bags returned to stock' : '');
            $this->notify->requestCancelled($id, byHospital: $by->isHospital(), wasDispatched: $status === RequestStatus::InTransit);
        });
    }

    /** "2 A-, 1 O-" */
    public static function describe(array $allocation): string
    {
        return implode(', ', array_map(
            static fn (string $code, int $bags) => "{$bags} {$code}",
            array_keys($allocation),
            $allocation,
        ));
    }

    /** @param array<string, int> $allocation */
    private function validateAllocation(BloodGroup $patient, int $requested, array $allocation): void
    {
        foreach (array_keys($allocation) as $code) {
            $donor = BloodGroup::tryFrom((string) $code)
                ?? throw new DomainException("Unknown blood type “{$code}”.");
            if (!$patient->isCompatibleDonor($donor)) {
                throw new DomainException("{$donor->value} is not compatible with a {$patient->value} patient.");
            }
        }

        $total = array_sum($allocation);
        if ($total > $requested) {
            throw new DomainException(sprintf('You allocated %d bags but only %d were requested.', $total, $requested));
        }
    }

    private function requireStaff(Actor $by): void
    {
        if (!$by->isAdmin()) {
            throw new DomainException('Only blood bank staff can do that.');
        }
    }

    /** @return array<string, mixed> */
    private function lockOpen(int $id, RequestStatus $next): array
    {
        $request = $this->requests->lock($id) ?? throw new DomainException('Request not found.');
        $current = RequestStatus::from($request['status']);

        if (!$current->canTransitionTo($next)) {
            throw new DomainException(sprintf(
                'This request is already %s and can no longer be changed that way.',
                strtolower($current->label()),
            ));
        }

        return $request;
    }
}
