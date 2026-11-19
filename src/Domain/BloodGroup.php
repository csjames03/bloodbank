<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * The eight ABO/Rh blood groups, with red-cell transfusion compatibility.
 */
enum BloodGroup: string
{
    case APos = 'A+';
    case ANeg = 'A-';
    case BPos = 'B+';
    case BNeg = 'B-';
    case ABPos = 'AB+';
    case ABNeg = 'AB-';
    case OPos = 'O+';
    case ONeg = 'O-';

    /** @return list<string> */
    public static function codes(): array
    {
        return array_map(static fn (self $g) => $g->value, self::cases());
    }

    public function abo(): string
    {
        return rtrim($this->value, '+-');
    }

    public function isPositive(): bool
    {
        return str_ends_with($this->value, '+');
    }

    /**
     * Groups a patient of this type can safely receive red cells from.
     *
     * @return list<self>
     */
    public function canReceiveFrom(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $donor) => $this->isCompatibleDonor($donor),
        ));
    }

    public function isCompatibleDonor(self $donor): bool
    {
        // Rh-negative patients can only receive Rh-negative blood.
        if (!$this->isPositive() && $donor->isPositive()) {
            return false;
        }

        return match ($this->abo()) {
            'AB' => true,
            'A' => in_array($donor->abo(), ['A', 'O'], true),
            'B' => in_array($donor->abo(), ['B', 'O'], true),
            'O' => $donor->abo() === 'O',
        };
    }

    /**
     * Compatible donor groups in the order staff should draw from them:
     * the patient's own group first, then other compatible groups with the
     * most stock, and O- (the universal donor, always scarce) last.
     *
     * @param array<string, int> $stock code => available bags
     * @return list<self>
     */
    public function preferredDonors(array $stock): array
    {
        $others = array_filter(
            $this->canReceiveFrom(),
            fn (self $g) => $g !== $this && $g !== self::ONeg,
        );
        usort($others, static fn (self $a, self $b) => ($stock[$b->value] ?? 0) <=> ($stock[$a->value] ?? 0));

        $order = [$this, ...$others];
        if ($this !== self::ONeg && $this->isCompatibleDonor(self::ONeg)) {
            $order[] = self::ONeg;
        }

        return $order;
    }

    /**
     * Default allocation for `$bags` bags given current stock, filling from
     * preferredDonors() in order. May total less than `$bags` when short.
     *
     * @param array<string, int> $stock
     * @return array<string, int> code => bags (only non-zero entries)
     */
    public function suggestAllocation(int $bags, array $stock): array
    {
        $plan = [];
        foreach ($this->preferredDonors($stock) as $group) {
            if ($bags <= 0) {
                break;
            }
            $take = min($bags, max(0, $stock[$group->value] ?? 0));
            if ($take > 0) {
                $plan[$group->value] = $take;
                $bags -= $take;
            }
        }

        return $plan;
    }

    /** CSS modifier so each group gets a consistent accent colour. */
    public function slug(): string
    {
        return strtolower(str_replace(['+', '-'], ['pos', 'neg'], $this->value));
    }
}
