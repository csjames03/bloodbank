<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Where a physical blood bag is in its life.
 *
 *   quarantine ──pass──▶ available ──dispatch──▶ dispatched
 *        │                  │  ▲                    │
 *        │                  │  └──── recall ────────┘
 *        └─ fail / discard ─┴──▶ discarded
 *   quarantine | available ──(past expiry)──▶ expired
 */
enum UnitStatus: string
{
    case Quarantine = 'quarantine';
    case Available = 'available';
    case Dispatched = 'dispatched';
    case Discarded = 'discarded';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Quarantine => 'Awaiting screening',
            self::Available => 'Available',
            self::Dispatched => 'Dispatched',
            self::Discarded => 'Discarded',
            self::Expired => 'Expired',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Quarantine => 'warning',
            self::Available => 'success',
            self::Dispatched => 'info',
            self::Discarded => 'danger',
            self::Expired => 'neutral',
        };
    }

    /** Units staff can still take out of circulation by hand. */
    public function canDiscard(): bool
    {
        return $this === self::Quarantine || $this === self::Available;
    }
}
