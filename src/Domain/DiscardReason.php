<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Why staff took a unit out of circulation by hand. Every discard needs one,
 * so wastage can be explained and reduced.
 */
enum DiscardReason: string
{
    case Damaged = 'damaged';
    case ColdChain = 'cold_chain';
    case Haemolysed = 'haemolysed';
    case Clotted = 'clotted';
    case Labelling = 'labelling';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Damaged => 'Bag damaged or leaking',
            self::ColdChain => 'Cold-chain breach (temperature)',
            self::Haemolysed => 'Haemolysed',
            self::Clotted => 'Clotted',
            self::Labelling => 'Labelling error',
            self::Other => 'Other',
        };
    }
}
