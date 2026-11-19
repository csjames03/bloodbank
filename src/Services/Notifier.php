<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Domain\NotificationType as Type;
use App\Domain\Priority;
use App\Domain\RequestStatus;
use App\Repositories\AccountRepository;
use App\Repositories\BloodTypeRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\RequestRepository;

/**
 * Turns business events into in-app notifications.
 *
 * Services call it inside their own transaction, so a notification is stored
 * only when the change that caused it is committed.
 */
final class Notifier
{
    private readonly NotificationRepository $inbox;
    private readonly RequestRepository $requests;
    private readonly BloodTypeRepository $bloodTypes;
    private readonly AccountRepository $accounts;

    public function __construct(?Database $db = null)
    {
        $db ??= Database::instance();
        $this->inbox = new NotificationRepository($db);
        $this->requests = new RequestRepository($db);
        $this->bloodTypes = new BloodTypeRepository($db);
        $this->accounts = new AccountRepository($db);
    }

    /** A new request: staff hear about hospital requests; hospitals hear about ones staff filed for them. */
    public function requestCreated(int $requestId, bool $filedByStaff): void
    {
        $r = $this->request($requestId);
        $what = sprintf('%s of %s', plural((int) $r['bags'], 'bag'), $r['blood_type']);

        if ($filedByStaff) {
            $this->inbox->toHospital(
                (int) $r['hospital_id'],
                Type::RequestFiled,
                'The blood bank filed a request for you',
                "{$what} for {$r['doctor_name']}, waiting for approval.",
                $this->link($requestId, RequestStatus::Pending),
                $requestId,
            );

            return;
        }

        $priority = Priority::from($r['priority']);
        $needed = $r['needed_by'] ? ' Needed by ' . fmt_date($r['needed_by'], 'M j, g:i A') . '.' : '';

        $this->inbox->toAdmins(
            $priority === Priority::Emergency ? Type::RequestEmergency : Type::RequestCreated,
            match ($priority) {
                Priority::Emergency => "EMERGENCY request from {$r['hospital_name']}",
                Priority::Urgent => "Urgent request from {$r['hospital_name']}",
                Priority::Routine => "New request from {$r['hospital_name']}",
            },
            "{$r['doctor_name']} needs {$what}.{$needed}",
            $this->link($requestId, RequestStatus::Pending),
            $requestId,
        );
    }

    /** @param array<string, int> $allocation code => bags actually sent */
    public function requestDispatched(int $requestId, array $allocation): void
    {
        $r = $this->request($requestId);
        $sent = array_sum($allocation);
        $substituted = array_diff(array_keys($allocation), [$r['blood_type']]) !== [];

        $body = sprintf('%s for %s left the blood bank.', plural($sent, 'bag'), $r['doctor_name']);
        if ($substituted) {
            $body = sprintf('Sending %s for %s. Compatible substitute for %s.', RequestService::describe($allocation), $r['doctor_name'], $r['blood_type']);
        }
        if ($sent < (int) $r['bags']) {
            $body .= sprintf(' Partial fill: %d of %d bags.', $sent, $r['bags']);
        }

        $this->inbox->toHospital(
            (int) $r['hospital_id'],
            Type::RequestDispatched,
            "Your {$r['blood_type']} request is on the way",
            $body,
            $this->link($requestId, RequestStatus::InTransit),
            $requestId,
        );
    }

    /** Hospital confirmed receipt (tell staff), or staff recorded it for them (tell the hospital). */
    public function requestReceived(int $requestId, bool $confirmedByHospital): void
    {
        $r = $this->request($requestId);
        $what = sprintf('%s for %s', plural((int) $r['sent'] ?: (int) $r['bags'], 'bag'), $r['doctor_name']);
        $link = $this->link($requestId, RequestStatus::Completed);

        if ($confirmedByHospital) {
            $this->inbox->toAdmins(Type::RequestReceived, "{$r['hospital_name']} received their delivery", ucfirst($what) . " ({$r['blood_type']}).", $link, $requestId);

            return;
        }

        $this->inbox->toHospital(
            (int) $r['hospital_id'],
            Type::RequestDelivered,
            "{$r['blood_type']} delivery recorded as received",
            ucfirst($what) . '. The blood bank recorded the hand-off for you.',
            $link,
            $requestId,
        );
    }

    /**
     * @param bool $byHospital the hospital withdrew it (tell staff) rather than staff cancelling it (tell the hospital)
     * @param bool $wasDispatched the bags were already on the way
     */
    public function requestCancelled(int $requestId, bool $byHospital, bool $wasDispatched): void
    {
        $r = $this->request($requestId);
        $what = sprintf('%s of %s for %s', plural((int) $r['bags'], 'bag'), $r['blood_type'], $r['doctor_name']);
        $link = $this->link($requestId, RequestStatus::Cancelled);

        if ($byHospital) {
            $this->inbox->toAdmins(Type::RequestWithdrawn, "{$r['hospital_name']} withdrew a request", ucfirst($what) . '.', $link, $requestId);

            return;
        }

        $this->inbox->toHospital(
            (int) $r['hospital_id'],
            Type::RequestCancelled,
            'The blood bank cancelled your request',
            ucfirst($what) . ($wasDispatched ? '. The dispatch was recalled.' : '.'),
            $link,
            $requestId,
        );
    }

    public function donationLogged(int $hospitalId, string $donorName, string $bloodType, int $bags): void
    {
        $hospital = $this->accounts->findHospital($hospitalId)['name'] ?? 'A hospital';

        $this->inbox->toAdmins(
            Type::DonationLogged,
            "{$hospital} logged a walk-in donor",
            sprintf('%s gave %s of %s.', $donorName, plural($bags, 'bag'), $bloodType),
            '/donations',
        );
    }

    /**
     * Call after stock went down by `$delta` (negative) to `$after`.
     * Alerts staff once, when a group crosses the low-stock threshold or runs out.
     */
    public function stockReduced(int $bloodTypeId, int $after, int $delta): void
    {
        $before = $after - $delta;
        $threshold = (int) config('inventory.low_stock_threshold', 5);
        $code = $this->bloodTypes->find($bloodTypeId)['code'] ?? 'Blood';

        if ($after === 0 && $before > 0) {
            $this->inbox->toAdmins(Type::StockOut, "{$code} is out of stock", 'No bags left. New requests for this group will be refused.', '/stocks');
        } elseif ($after > 0 && $after <= $threshold && $before > $threshold) {
            $this->inbox->toAdmins(Type::StockLow, "{$code} is running low", sprintf('Only %s left.', plural($after, 'bag')), '/stocks');
        }
    }

    /** @return array<string, mixed> */
    private function request(int $id): array
    {
        return $this->requests->find($id) ?? throw new \LogicException("Request {$id} not found.");
    }

    /** Deep link to the request card on the Requests page, filtered to its new status. */
    private function link(int $requestId, RequestStatus $status): string
    {
        return '/requests?status=' . $status->value . '#request-' . $requestId;
    }
}
