<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Lifecycle of a hospital blood request.
 *
 *   pending ──approve──▶ in_transit ──receive──▶ completed
 *      │                     │
 *      └──────cancel─────────┴──────▶ cancelled
 *
 * Stock is deducted when a request is approved (dispatched) and returned
 * if an in-transit request is cancelled.
 */
enum RequestStatus: string
{
    case Pending = 'pending';
    case InTransit = 'in_transit';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InTransit => 'In transit',
            self::Completed => 'Received',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Badge colour modifier used by the CSS. */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::InTransit => 'info',
            self::Completed => 'success',
            self::Cancelled => 'neutral',
        };
    }

    /** Progress step 1–3 for the delivery tracker (0 = cancelled). */
    public function step(): int
    {
        return match ($this) {
            self::Pending => 1,
            self::InTransit => 2,
            self::Completed => 3,
            self::Cancelled => 0,
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending => in_array($next, [self::InTransit, self::Cancelled], true),
            self::InTransit => in_array($next, [self::Completed, self::Cancelled], true),
            self::Completed, self::Cancelled => false,
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Pending || $this === self::InTransit;
    }
}
