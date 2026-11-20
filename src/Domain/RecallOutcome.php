<?php

declare(strict_types=1);

namespace App\Domain;

/** Result of calling a donor during a recall drive. */
enum RecallOutcome: string
{
    case Booked = 'booked';
    case Reached = 'reached';
    case NoAnswer = 'no_answer';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::Booked => 'Booked to donate',
            self::Reached => 'Reached, will think about it',
            self::NoAnswer => 'No answer',
            self::Declined => 'Declined',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Booked => 'success',
            self::Reached => 'info',
            self::NoAnswer => 'neutral',
            self::Declined => 'danger',
        };
    }
}
