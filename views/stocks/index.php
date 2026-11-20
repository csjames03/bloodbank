<?php
/**
 * @var list<array<string, mixed>> $inventory
 * @var array{available: int, expiring: int, quarantine: int, wasted: int, donated: int, dispatched: int, pending: int} $totals
 * @var array<string, array<string, mixed>> $supply  code => SupplyService forecast row
 */
use App\Domain\BloodGroup;
use App\Services\SupplyService;

$window = (int) config('supply.usage_window_days');
$critical = (float) config('supply.critical_days');
$low = (float) config('supply.low_days');
$warn = (int) config('inventory.expiry_warning_days');
$shelf = (int) config('inventory.shelf_life_days');
$max = max(1, ...array_map(static fn ($i) => (int) $i['bags'], $inventory));
?>
<div class="page-head">
    <div>
        <h1>Blood stock</h1>
        <p>Usable units per group: screened, unexpired and in storage. Whole blood keeps <?= $shelf ?> days. <strong>Days of supply</strong> = available units ÷ average daily use over the last <?= $window ?> days: under <?= $low + 0 ?> days is Low, under <?= $critical + 0 ?> is Critical.</p>
    </div>
    <div class="page-actions">
        <a class="btn" href="<?= e(url('/units', ['status' => 'expiring'])) ?>"><?= icon('clock', 'icon-sm') ?> Expiring units</a>
        <a class="btn" href="<?= e(url('/requests', ['status' => 'pending'])) ?>"><?= icon('clipboard', 'icon-sm') ?> Pending requests</a>
    </div>
</div>

<section class="stats" aria-label="Inventory totals">
    <a href="<?= e(url('/units', ['status' => 'available'])) ?>" class="card stat tone-primary">
        <div class="stat-top"><span class="stat-label">Available</span><span class="stat-icon"><?= icon('droplet') ?></span></div>
        <div class="stat-value"><?= $totals['available'] ?></div>
        <div class="stat-meta"><?= $totals['pending'] ?> bags of pending demand</div>
    </a>
    <a href="<?= e(url('/units', ['status' => 'expiring'])) ?>" class="card stat tone-warning">
        <div class="stat-top"><span class="stat-label">Expiring ≤ <?= $warn ?> days</span><span class="stat-icon"><?= icon('clock') ?></span></div>
        <div class="stat-value"><?= $totals['expiring'] ?></div>
        <div class="stat-meta">dispatched first automatically</div>
    </a>
    <a href="<?= e(url('/screening')) ?>" class="card stat tone-info">
        <div class="stat-top"><span class="stat-label">In quarantine</span><span class="stat-icon"><?= icon('shield') ?></span></div>
        <div class="stat-value"><?= $totals['quarantine'] ?></div>
        <div class="stat-meta">units awaiting screening</div>
    </a>
    <a href="<?= e(url('/units', ['status' => 'expired'])) ?>" class="card stat">
        <div class="stat-top"><span class="stat-label">Wasted (30 days)</span><span class="stat-icon"><?= icon('trash') ?></span></div>
        <div class="stat-value"><?= $totals['wasted'] ?></div>
        <div class="stat-meta">expired or discarded</div>
    </a>
</section>

<section class="card table-wrap section">
    <div class="card-head"><h2>Inventory by blood group</h2></div>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Group</th>
                <th scope="col" style="width:22%">Available</th>
                <th scope="col">Days of supply</th>
                <th scope="col" class="num">Expiring</th>
                <th scope="col">Next expiry</th>
                <th scope="col" class="num">Quarantine</th>
                <th scope="col" class="num">Pending demand</th>
                <th scope="col">Supply</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($inventory as $row):
                $bags = (int) $row['bags'];
                $pending = (int) $row['pending'];
                $sup = $supply[$row['code']];
                /** @var App\Domain\SupplyLevel $level */
                $level = $sup['level'];
                $tile = match ($level->value) { 'out' => 'is-empty', 'low', 'critical' => 'is-low', default => '' };
            ?>
                <tr>
                    <td data-label="Group"><a href="<?= e(url('/units', ['type' => $row['code'], 'status' => 'available'])) ?>" class="blood-code"><?= e($row['code']) ?></a></td>
                    <td data-label="Available" class="cell-full">
                        <div class="blood-tile <?= $tile ?>" style="border:0;padding:0;background:none;gap:6px">
                            <div class="blood-bags" style="font-size:18px"><?= $bags ?><small><?= $bags === 1 ? 'bag' : 'bags' ?></small></div>
                            <div class="meter"><span style="width: <?= round($bags / $max * 100) ?>%"></span></div>
                        </div>
                    </td>
                    <td data-label="Days of supply">
                        <strong class="tone-text-<?= e($level->tone() === 'success' ? 'neutral' : $level->tone()) ?>"><?= e(SupplyService::formatDays($sup['days'])) ?></strong>
                        <div class="list-sub"><?= e(rtrim(rtrim(number_format($sup['daily_use'], 2), '0'), '.') ?: '0') ?>/day used</div>
                    </td>
                    <td data-label="Expiring" class="num"><?= (int) $row['expiring'] ?: '—' ?></td>
                    <td data-label="Next expiry">
                        <?php if ($row['next_expiry']): ?>
                            <span class="tone-text-<?= e(expiry_tone($row['next_expiry'])) ?>"><?= e(fmt_date($row['next_expiry'], 'M j')) ?></span>
                            <div class="list-sub"><?= e(expiry_label($row['next_expiry'])) ?></div>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td data-label="Quarantine" class="num"><?= (int) $row['quarantine'] ?: '—' ?></td>
                    <td data-label="Pending" class="num"><?= $pending ?: '—' ?></td>
                    <td data-label="Supply">
                        <span class="badge badge-<?= e($level->tone()) ?>"><?= e($level->label()) ?></span>
                        <?php if ($pending > $bags): ?><span class="badge badge-danger plain">Short <?= $pending - $bags ?></span><?php endif; ?>
                        <?php if ($level->needsRecall()): ?>
                            <div><a class="link" style="font-size:12.5px" href="<?= e(url('/recall', ['group' => $row['code']])) ?>">Recall <?= plural((int) $sup['eligible'], 'donor') ?></a></div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card section" aria-labelledby="compat-heading">
    <div class="card-head">
        <div>
            <h2 id="compat-heading">Red-cell compatibility</h2>
            <p class="muted" style="font-size:13px;margin-top:2px">Which donor groups each patient group can safely receive.</p>
        </div>
    </div>
    <div class="matrix-wrap">
        <table class="matrix">
            <thead>
                <tr>
                    <th scope="col">Patient ↓ / Donor →</th>
                    <?php foreach (BloodGroup::cases() as $donor): ?>
                        <th scope="col"><?= e($donor->value) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach (BloodGroup::cases() as $patient): ?>
                    <tr>
                        <th scope="row"><?= e($patient->value) ?></th>
                        <?php foreach (BloodGroup::cases() as $donor): ?>
                            <td>
                                <?php if ($patient->isCompatibleDonor($donor)): ?>
                                    <span class="yes" title="<?= e($patient->value) ?> can receive <?= e($donor->value) ?>"><?= icon('check', 'icon-sm') ?><span class="sr-only">Compatible</span></span>
                                <?php else: ?>
                                    <span class="no" aria-label="Not compatible">·</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
