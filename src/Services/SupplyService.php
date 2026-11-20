<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Domain\SupplyLevel;
use App\Repositories\BloodTypeRepository;
use App\Repositories\DonorRepository;

/**
 * Days of supply per blood group: how long stock lasts at the recent rate
 * of use, and what that means (Healthy / Low / Critical / Out).
 */
final class SupplyService
{
    private readonly BloodTypeRepository $bloodTypes;
    private readonly DonorRepository $donors;

    /** @var array<string, int>|null cached issue counts for this request */
    private ?array $issued = null;

    public function __construct(?Database $db = null)
    {
        $db ??= Database::instance();
        $this->bloodTypes = new BloodTypeRepository($db);
        $this->donors = new DonorRepository($db);
    }

    /**
     * One row per group, in display order.
     *
     * @return list<array{code: string, available: int, daily_use: float, days: ?float, level: SupplyLevel, eligible: int}>
     */
    public function forecast(): array
    {
        $eligible = $this->donors->eligibleCounts();

        return array_map(function (array $g) use ($eligible): array {
            $daily = $this->dailyUse($g['code']);
            $available = (int) $g['bags'];

            return [
                'code'      => $g['code'],
                'available' => $available,
                'daily_use' => $daily,
                'days'      => $daily > 0 ? $available / $daily : null,
                'level'     => $this->level($available, $daily),
                'eligible'  => $eligible[$g['code']] ?? 0,
            ];
        }, $this->bloodTypes->all());
    }

    /** @return array<string, array<string, mixed>> code => forecast row */
    public function byCode(): array
    {
        return array_column($this->forecast(), null, 'code');
    }

    /**
     * What hospitals may see: a level per group, no counts.
     *
     * @return array<string, array{level: string, label: string}>
     */
    public function hospitalLevels(): array
    {
        $out = [];
        foreach ($this->forecast() as $row) {
            $out[$row['code']] = ['level' => $row['level']->value, 'label' => $row['level']->hospitalLabel()];
        }

        return $out;
    }

    /** Average units issued per day over the usage window. */
    public function dailyUse(string $code): float
    {
        $window = max(1, (int) config('supply.usage_window_days', 30));
        $this->issued ??= $this->bloodTypes->issuedSince($window);

        return ($this->issued[$code] ?? 0) / $window;
    }

    public function level(int $available, float $dailyUse): SupplyLevel
    {
        return SupplyLevel::assess(
            $available,
            $dailyUse,
            (float) config('supply.critical_days', 3),
            (float) config('supply.low_days', 7),
            (int) config('inventory.low_stock_threshold', 5),
        );
    }

    public function levelFor(string $code, int $available): SupplyLevel
    {
        return $this->level($available, $this->dailyUse($code));
    }

    public function eligibleDonors(string $code): int
    {
        return $this->donors->eligibleCounts()[$code] ?? 0;
    }

    /** "≈ 4.5 days" / "> 60 days" / "no recent use" */
    public static function formatDays(?float $days): string
    {
        return match (true) {
            $days === null => 'no recent use',
            $days >= 60 => '> 60 days',
            $days < 1 => '< 1 day',
            default => '≈ ' . rtrim(rtrim(number_format($days, 1), '0'), '.') . ' days',
        };
    }
}
