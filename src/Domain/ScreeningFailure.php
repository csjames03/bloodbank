<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Why a donation failed lab screening, and what that means for the donor.
 */
enum ScreeningFailure: string
{
    case Hiv = 'hiv';
    case HepatitisB = 'hbv';
    case HepatitisC = 'hcv';
    case Syphilis = 'syphilis';
    case Malaria = 'malaria';
    case Sample = 'sample';

    public function label(): string
    {
        return match ($this) {
            self::Hiv => 'HIV reactive',
            self::HepatitisB => 'Hepatitis B reactive',
            self::HepatitisC => 'Hepatitis C reactive',
            self::Syphilis => 'Syphilis reactive',
            self::Malaria => 'Malaria positive',
            self::Sample => 'Sample problem (clotted, insufficient volume, haemolysed)',
        };
    }

    /** Never donate again. */
    public function isPermanentDeferral(): bool
    {
        return in_array($this, [self::Hiv, self::HepatitisB, self::HepatitisC], true);
    }

    /** Days the donor is deferred for, when the deferral is temporary; null for none. */
    public function deferralDays(): ?int
    {
        return match ($this) {
            self::Syphilis, self::Malaria => 365,
            self::Sample => null,
            default => null,
        };
    }
}
