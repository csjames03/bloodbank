<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Domain\Actor;
use App\Domain\DiscardReason;
use App\Domain\DomainException;
use App\Domain\UnitStatus;
use App\Repositories\ActivityRepository;
use App\Repositories\BloodTypeRepository;
use App\Repositories\UnitRepository;

/**
 * Unit-level stock housekeeping: expiry and manual discards.
 */
final class InventoryService
{
    private readonly Database $db;
    private readonly UnitRepository $units;
    private readonly BloodTypeRepository $bloodTypes;
    private readonly ActivityRepository $activity;
    private readonly Notifier $notify;

    public function __construct()
    {
        $this->db = Database::instance();
        $this->units = new UnitRepository($this->db);
        $this->bloodTypes = new BloodTypeRepository($this->db);
        $this->activity = new ActivityRepository($this->db);
        $this->notify = new Notifier($this->db);
    }

    /**
     * Expire units past their date and warn about units entering the
     * warning window. Cheap and idempotent: runs on every signed-in request,
     * and each unit is handled exactly once even under concurrency.
     *
     * @return array{expired: int, expiring: int}
     */
    public function sweep(): array
    {
        return $this->db->transaction(function (): array {
            $expired = $this->units->expireDue();

            if ($expired !== []) {
                $byCode = array_count_values(array_column($expired, 'code'));
                $this->notify->unitsExpired($byCode);
                $this->activity->log('unit', 0, 'expired', Actor::system(), sprintf(
                    '%s expired: %s',
                    plural(count($expired), 'unit'),
                    self::summarise($byCode),
                ));

                // Expiry of available units lowers stock; alert if a group crossed a threshold.
                $lost = [];
                foreach ($expired as $u) {
                    if ($u['was'] === 'available') {
                        $lost[$u['blood_type_id']] = ($lost[$u['blood_type_id']] ?? 0) + 1;
                    }
                }
                foreach ($lost as $typeId => $n) {
                    $this->notify->stockReduced($typeId, $this->bloodTypes->available($typeId), -$n);
                }
            }

            $expiring = $this->units->flagExpiringSoon((int) config('inventory.expiry_warning_days', 7));
            if ($expiring !== []) {
                $this->notify->unitsExpiring(array_count_values(array_column($expiring, 'code')));
            }

            return ['expired' => count($expired), 'expiring' => count($expiring)];
        });
    }

    /** Take one unit out of circulation, with a reason (staff only). */
    public function discard(int $unitId, DiscardReason $reason, string $note, Actor $by): void
    {
        if (!$by->isAdmin()) {
            throw new DomainException('Only blood bank staff can discard units.');
        }

        $this->db->transaction(function () use ($unitId, $reason, $note, $by): void {
            $unit = $this->units->lock($unitId) ?? throw new DomainException('Unit not found.');
            $status = UnitStatus::from($unit['status']);

            if (!$status->canDiscard()) {
                throw new DomainException(sprintf('This unit is %s and cannot be discarded.', strtolower($status->label())));
            }

            $text = $reason->label() . ($note !== '' ? ' — ' . $note : '');
            $this->units->discard($unitId, $text);
            $code = $this->bloodTypes->find((int) $unit['blood_type_id'])['code'] ?? '?';
            $this->activity->log('unit', $unitId, 'discarded', $by, "{$code}: {$text}");

            if ($status === UnitStatus::Available) {
                $typeId = (int) $unit['blood_type_id'];
                $this->notify->stockReduced($typeId, $this->bloodTypes->available($typeId), -1);
            }
        });
    }

    /** "A+ ×3, O- ×1" */
    public static function summarise(array $byCode): string
    {
        ksort($byCode);

        return implode(', ', array_map(static fn ($code, $n) => "{$code} ×{$n}", array_keys($byCode), $byCode));
    }
}
