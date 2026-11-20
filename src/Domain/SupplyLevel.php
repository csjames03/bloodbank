<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * How long a blood group's stock will last at the current rate of use.
 *
 * Days of supply = usable units ÷ average daily use. A fixed bag count means
 * little on its own: 6 bags is plenty for a group used once a week and an
 * emergency for one used five times a day.
 */
enum SupplyLevel: string
{
    case Healthy = 'healthy';
    case Low = 'low';
    case Critical = 'critical';
    case Out = 'out';

    /**
     * @param float $dailyUse average units issued per day (0 when there is no history)
     * @param int   $fallbackThreshold bag count used when there is no usage history
     */
    public static function assess(int $available, float $dailyUse, float $criticalDays, float $lowDays, int $fallbackThreshold): self
    {
        if ($available <= 0) {
            return self::Out;
        }
        if ($dailyUse <= 0) {
            return $available <= $fallbackThreshold ? self::Low : self::Healthy;
        }

        $days = $available / $dailyUse;

        return match (true) {
            $days < $criticalDays => self::Critical,
            $days < $lowDays => self::Low,
            default => self::Healthy,
        };
    }

    /** Higher is worse. */
    public function severity(): int
    {
        return match ($this) {
            self::Healthy => 0,
            self::Low => 1,
            self::Critical => 2,
            self::Out => 3,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Healthy => 'Healthy',
            self::Low => 'Low',
            self::Critical => 'Critical',
            self::Out => 'Out of stock',
        };
    }

    /** What hospitals see instead of exact counts. */
    public function hospitalLabel(): string
    {
        return match ($this) {
            self::Healthy => 'Available',
            self::Low => 'Limited',
            self::Critical => 'Critical',
            self::Out => 'Unavailable',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Healthy => 'success',
            self::Low => 'warning',
            self::Critical, self::Out => 'danger',
        };
    }

    /** Whether staff should start calling donors. */
    public function needsRecall(): bool
    {
        return $this->severity() >= self::Low->severity();
    }
}
