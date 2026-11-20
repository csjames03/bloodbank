<?php
/**
 * One tile per blood group.
 *
 * @var list<array{code: string, bags: int|string}> $items
 * @var array<string, array<string, mixed>> $supply  code => SupplyService forecast row
 * @var bool|null $wide
 * @var bool|null $linkToDonations
 * @var bool|null $bands   hospitals: show availability levels instead of exact counts
 */
use App\Services\SupplyService;

$max = max(1, ...array_map(static fn ($i) => (int) $i['bags'], $items));
$bands = !empty($bands);
?>
<div class="blood-grid <?= !empty($wide) ? 'wide' : '' ?>">
    <?php foreach ($items as $item):
        $bags = (int) $item['bags'];
        $row = $supply[$item['code']] ?? null;
        /** @var App\Domain\SupplyLevel|null $level */
        $level = $row['level'] ?? null;
        $state = match ($level?->value) {
            'out' => 'is-empty',
            'critical', 'low' => 'is-low',
            default => '',
        };
        $tag = !empty($linkToDonations) ? 'a' : 'div';
        $href = !empty($linkToDonations) ? ' href="' . e(url('/units', ['type' => $item['code'], 'status' => 'available'])) . '"' : '';
    ?>
        <<?= $tag . $href ?> class="blood-tile <?= $state ?> <?= $bands ? 'is-band level-' . e($level?->value ?? 'healthy') : '' ?>">
            <div class="blood-tile-top">
                <span class="blood-code"><?= e($item['code']) ?></span>
                <?php if ($level && !$bands && $level->needsRecall()): ?>
                    <span class="badge badge-<?= e($level->tone()) ?> plain"><?= e($level === App\Domain\SupplyLevel::Out ? 'Out' : $level->label()) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($bands): ?>
                <div class="band-label"><span class="band-dot"></span><?= e($level?->hospitalLabel() ?? 'Available') ?></div>
            <?php else: ?>
                <div class="blood-bags"><?= $bags ?><small><?= $bags === 1 ? 'bag' : 'bags' ?></small></div>
                <div class="meter" aria-hidden="true"><span style="width: <?= round($bags / $max * 100) ?>%"></span></div>
                <?php if ($row): ?><div class="tile-days"><?= e(SupplyService::formatDays($row['days'])) ?></div><?php endif; ?>
            <?php endif; ?>
        </<?= $tag ?>>
    <?php endforeach; ?>
</div>
