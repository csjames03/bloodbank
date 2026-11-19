<?php
/**
 * @var list<array{code: string, bags: int|string}> $items
 * @var bool|null $wide
 * @var bool|null $linkToDonations
 */
$threshold = (int) config('inventory.low_stock_threshold');
$max = max(1, ...array_map(static fn ($i) => (int) $i['bags'], $items));
?>
<div class="blood-grid <?= !empty($wide) ? 'wide' : '' ?>">
    <?php foreach ($items as $item):
        $bags = (int) $item['bags'];
        $state = $bags === 0 ? 'is-empty' : ($bags <= $threshold ? 'is-low' : '');
        $tag = !empty($linkToDonations) ? 'a' : 'div';
        $href = !empty($linkToDonations) ? ' href="' . e(url('/donations', ['type' => $item['code']])) . '"' : '';
    ?>
        <<?= $tag . $href ?> class="blood-tile <?= $state ?>">
            <div class="blood-tile-top">
                <span class="blood-code"><?= e($item['code']) ?></span>
                <?php if ($bags === 0): ?>
                    <span class="badge badge-danger plain">Out</span>
                <?php elseif ($bags <= $threshold): ?>
                    <span class="badge badge-warning plain">Low</span>
                <?php endif; ?>
            </div>
            <div class="blood-bags"><?= $bags ?><small><?= $bags === 1 ? 'bag' : 'bags' ?></small></div>
            <div class="meter" aria-hidden="true"><span style="width: <?= round($bags / $max * 100) ?>%"></span></div>
        </<?= $tag ?>>
    <?php endforeach; ?>
</div>
