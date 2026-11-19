<?php
/**
 * @var list<array<string, mixed>> $inventory
 * @var array{available: int, donated: int, dispatched: int, pending: int} $totals
 */
use App\Domain\BloodGroup;

$threshold = (int) config('inventory.low_stock_threshold');
$max = max(1, ...array_map(static fn ($i) => (int) $i['bags'], $inventory));
?>
<div class="page-head">
    <div>
        <h1>Blood stock</h1>
        <p>Live inventory for all eight blood groups. Groups at or below <?= plural($threshold, 'bag') ?> are flagged as low.</p>
    </div>
    <div class="page-actions">
        <a class="btn" href="<?= e(url('/requests', ['status' => 'pending'])) ?>"><?= icon('clipboard', 'icon-sm') ?> Pending requests</a>
    </div>
</div>

<section class="stats" aria-label="Inventory totals">
    <div class="card stat tone-primary">
        <div class="stat-top"><span class="stat-label">Available</span><span class="stat-icon"><?= icon('droplet') ?></span></div>
        <div class="stat-value"><?= $totals['available'] ?></div>
        <div class="stat-meta">bags ready to dispatch</div>
    </div>
    <div class="card stat tone-success">
        <div class="stat-top"><span class="stat-label">Collected</span><span class="stat-icon"><?= icon('heart') ?></span></div>
        <div class="stat-value"><?= $totals['donated'] ?></div>
        <div class="stat-meta">bags donated all-time</div>
    </div>
    <div class="card stat tone-info">
        <div class="stat-top"><span class="stat-label">Dispatched</span><span class="stat-icon"><?= icon('truck') ?></span></div>
        <div class="stat-value"><?= $totals['dispatched'] ?></div>
        <div class="stat-meta">bags sent to hospitals</div>
    </div>
    <div class="card stat tone-warning">
        <div class="stat-top"><span class="stat-label">Pending demand</span><span class="stat-icon"><?= icon('clock') ?></span></div>
        <div class="stat-value"><?= $totals['pending'] ?></div>
        <div class="stat-meta">bags awaiting approval</div>
    </div>
</section>

<section class="card table-wrap section">
    <div class="card-head"><h2>Inventory by blood group</h2></div>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Group</th>
                <th scope="col" style="width:32%">Available</th>
                <th scope="col" class="num">Collected</th>
                <th scope="col" class="num">Dispatched</th>
                <th scope="col" class="num">Pending demand</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($inventory as $row):
                $bags = (int) $row['bags'];
                $pending = (int) $row['pending'];
                $tile = $bags === 0 ? 'is-empty' : ($bags <= $threshold ? 'is-low' : '');
            ?>
                <tr>
                    <td data-label="Group"><span class="blood-code"><?= e($row['code']) ?></span></td>
                    <td data-label="Available" class="cell-full">
                        <div class="blood-tile <?= $tile ?>" style="border:0;padding:0;background:none;gap:6px">
                            <div class="blood-bags" style="font-size:18px"><?= $bags ?><small><?= $bags === 1 ? 'bag' : 'bags' ?></small></div>
                            <div class="meter"><span style="width: <?= round($bags / $max * 100) ?>%"></span></div>
                        </div>
                    </td>
                    <td data-label="Collected" class="num"><?= (int) $row['donated'] ?></td>
                    <td data-label="Dispatched" class="num"><?= (int) $row['dispatched'] ?></td>
                    <td data-label="Pending" class="num"><?= $pending ?></td>
                    <td data-label="Status">
                        <?php if ($bags === 0): ?>
                            <span class="badge badge-danger">Out of stock</span>
                        <?php elseif ($pending > $bags): ?>
                            <span class="badge badge-danger">Short <?= $pending - $bags ?></span>
                        <?php elseif ($bags <= $threshold): ?>
                            <span class="badge badge-warning">Low</span>
                        <?php else: ?>
                            <span class="badge badge-success">Healthy</span>
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
