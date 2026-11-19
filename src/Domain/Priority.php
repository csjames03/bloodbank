<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Clinical urgency of a blood request. Drives queue order and alerts.
 */
enum Priority: string
{
    case Routine = 'routine';
    case Urgent = 'urgent';
    case Emergency = 'emergency';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Badge colour modifier. */
    public function tone(): string
    {
        return match ($this) {
            self::Routine => 'neutral',
            self::Urgent => 'warning',
            self::Emergency => 'danger',
        };
    }

    /** Lower sorts first in the queue. */
    public function rank(): int
    {
        return match ($this) {
            self::Emergency => 0,
            self::Urgent => 1,
            self::Routine => 2,
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Routine => 'Planned procedure or restock',
            self::Urgent => 'Needed within hours',
            self::Emergency => 'Life-threatening, needed now',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $p) => $p->value, self::cases());
    }
}
