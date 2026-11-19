<?php
/**
 * @var list<array<string, mixed>> $inventory
 * @var int $totalStock
 * @var array<string, array{count: int, bags: int}> $summary
 * @var array{total_bags: int, month_bags: int, donors: int} $donations
 * @var list<array<string, mixed>> $recentDonations
 * @var list<array<string, mixed>> $openRequests
 * @var array<string, int> $stock
 * @var list<array<string, mixed>> $hospitals
 */
$threshold = (int) config('inventory.low_stock_threshold');
$low = array_filter($inventory, static fn ($i) => (int) $i['bags'] <= $threshold);
$firstName = explode(' ', auth_user()['name'])[0];
?>
<div class="page-head">
    <div>
        <h1>Good <?= (int) date('G') < 12 ? 'morning' : ((int) date('G') < 18 ? 'afternoon' : 'evening') ?>, <?= e($firstName) ?></h1>
        <p><?= e(date('l, F j, Y')) ?> · Here’s the blood bank at a glance.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn" data-open-dialog="request-create"><?= icon('clipboard', 'icon-sm') ?> New request</button>
        <button type="button" class="btn btn-primary" data-open-dialog="donation-create"><?= icon('plus', 'icon-sm') ?> Record donation</button>
    </div>
</div>

<section class="stats" aria-label="Key figures">
    <a href="<?= e(url('/stocks')) ?>" class="card stat tone-primary">
        <div class="stat-top"><span class="stat-label">Bags in stock</span><span class="stat-icon"><?= icon('droplet') ?></span></div>
        <div class="stat-value"><?= $totalStock ?></div>
        <div class="stat-meta"><?= $low ? count($low) . ' group' . (count($low) > 1 ? 's' : '') . ' running low' : 'All groups healthy' ?></div>
    </a>
    <a href="<?= e(url('/requests', ['status' => 'pending'])) ?>" class="card stat tone-warning">
        <div class="stat-top"><span class="stat-label">Pending requests</span><span class="stat-icon"><?= icon('clock') ?></span></div>
        <div class="stat-value"><?= $summary['pending']['count'] ?></div>
        <div class="stat-meta"><?= plural($summary['pending']['bags'], 'bag') ?> awaiting approval</div>
    </a>
    <a href="<?= e(url('/requests', ['status' => 'in_transit'])) ?>" class="card stat tone-info">
        <div class="stat-top"><span class="stat-label">In transit</span><span class="stat-icon"><?= icon('truck') ?></span></div>
        <div class="stat-value"><?= $summary['in_transit']['count'] ?></div>
        <div class="stat-meta"><?= plural($summary['completed']['count'], 'delivery', 'deliveries') ?> completed</div>
    </a>
    <a href="<?= e(url('/donations')) ?>" class="card stat tone-success">
        <div class="stat-top"><span class="stat-label">Donated this month</span><span class="stat-icon"><?= icon('heart') ?></span></div>
        <div class="stat-value"><?= $donations['month_bags'] ?></div>
        <div class="stat-meta"><?= $donations['total_bags'] ?> bags from <?= plural($donations['donors'], 'donor') ?> all-time</div>
    </a>
</section>

<section class="section" aria-labelledby="stock-heading">
    <div class="section-head">
        <h2 id="stock-heading">Stock by blood group</h2>
        <a class="link" href="<?= e(url('/stocks')) ?>">Details <?= icon('arrow-right', 'icon-sm') ?></a>
    </div>
    <?php $items = $inventory; $wide = true; $linkToDonations = true; require BASE_PATH . '/views/partials/blood-grid.php'; ?>
</section>

<div class="grid-2 section">
    <section class="card" aria-labelledby="requests-heading">
        <div class="card-head">
            <h2 id="requests-heading">Requests &amp; deliveries</h2>
            <a class="link" href="<?= e(url('/requests')) ?>">View all <?= icon('arrow-right', 'icon-sm') ?></a>
        </div>
        <?php if (!$openRequests): ?>
            <div class="empty">
                <div class="empty-icon"><?= icon('clipboard', 'icon-lg') ?></div>
                <h3>No requests yet</h3>
                <p>Requests from partner hospitals will show up here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($openRequests as $r): $showHospital = true; require BASE_PATH . '/views/partials/request-item.php'; endforeach; ?>
        <?php endif; ?>
    </section>

    <section class="card" aria-labelledby="donations-heading" style="align-self:start">
        <div class="card-head">
            <h2 id="donations-heading">Recent donations</h2>
            <a class="link" href="<?= e(url('/donations')) ?>">View all <?= icon('arrow-right', 'icon-sm') ?></a>
        </div>
        <?php if (!$recentDonations): ?>
            <div class="empty">
                <div class="empty-icon"><?= icon('heart', 'icon-lg') ?></div>
                <h3>No donations yet</h3>
                <p>Record the first donation to start building stock.</p>
            </div>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($recentDonations as $d): ?>
                    <li class="list-item">
                        <span class="avatar avatar-sm"><?= e(initials($d['first_name'] . ' ' . $d['last_name'])) ?></span>
                        <div class="list-main">
                            <div class="list-title"><?= e($d['donor_name']) ?></div>
                            <div class="list-sub"><?= e($d['address']) ?></div>
                        </div>
                        <div class="list-end">
                            <span class="blood-code" style="min-width:38px;height:24px;font-size:13px"><?= e($d['blood_type']) ?></span>
                            <span class="list-sub"><?= e(time_ago($d['donated_at'])) ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php $dialogId = 'donation-create'; require BASE_PATH . '/views/partials/donation-dialog.php'; ?>
<?php require BASE_PATH . '/views/partials/request-dialog.php'; ?>
<?php if (is_admin()) { require BASE_PATH . '/views/partials/allocate-dialog.php'; } ?>
