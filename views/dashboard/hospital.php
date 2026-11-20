<?php
/**
 * @var array<string, mixed> $hospital
 * @var list<array<string, mixed>> $stock       groups (counts withheld)
 * @var array<string, array<string, mixed>> $supply
 * @var array<string, array{level: string, label: string}> $levels
 * @var array<string, array{count: int, bags: int}> $summary
 * @var list<array<string, mixed>> $requests
 */
?>
<div class="page-head">
    <div>
        <h1><?= e($hospital['name']) ?></h1>
        <p><?= e($hospital['address']) ?></p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" data-open-dialog="request-create"><?= icon('plus', 'icon-sm') ?> Request blood</button>
    </div>
</div>

<section class="stats" aria-label="Your requests">
    <div class="card stat tone-warning">
        <div class="stat-top"><span class="stat-label">Pending</span><span class="stat-icon"><?= icon('clock') ?></span></div>
        <div class="stat-value"><?= $summary['pending']['count'] ?></div>
        <div class="stat-meta"><?= plural($summary['pending']['bags'], 'bag') ?> requested</div>
    </div>
    <div class="card stat tone-info">
        <div class="stat-top"><span class="stat-label">On the way</span><span class="stat-icon"><?= icon('truck') ?></span></div>
        <div class="stat-value"><?= $summary['in_transit']['count'] ?></div>
        <div class="stat-meta"><?= plural($summary['in_transit']['bags'], 'bag') ?> in transit</div>
    </div>
    <div class="card stat tone-success">
        <div class="stat-top"><span class="stat-label">Received</span><span class="stat-icon"><?= icon('check') ?></span></div>
        <div class="stat-value"><?= $summary['completed']['count'] ?></div>
        <div class="stat-meta"><?= plural($summary['completed']['bags'], 'bag') ?> received</div>
    </div>
    <div class="card stat">
        <div class="stat-top"><span class="stat-label">Cancelled</span><span class="stat-icon"><?= icon('ban') ?></span></div>
        <div class="stat-value"><?= $summary['cancelled']['count'] ?></div>
        <div class="stat-meta">All-time</div>
    </div>
</section>

<div class="grid-2 section">
    <section class="card" aria-labelledby="deliveries-heading">
        <div class="card-head">
            <h2 id="deliveries-heading">Your deliveries</h2>
            <a class="link" href="<?= e(url('/requests')) ?>">View all <?= icon('arrow-right', 'icon-sm') ?></a>
        </div>
        <?php if (!$requests): ?>
            <div class="empty">
                <div class="empty-icon"><?= icon('truck', 'icon-lg') ?></div>
                <h3>No requests yet</h3>
                <p>When you request blood, you can follow its delivery here.</p>
                <button type="button" class="btn btn-primary" style="margin-top:8px" data-open-dialog="request-create"><?= icon('plus', 'icon-sm') ?> Request blood</button>
            </div>
        <?php else: ?>
            <?php foreach ($requests as $r): $showHospital = false; require BASE_PATH . '/views/partials/request-item.php'; endforeach; ?>
        <?php endif; ?>
    </section>

    <div class="stack" style="align-self:start">
        <section class="card card-pad" aria-labelledby="availability-heading">
            <div class="section-head">
                <h2 id="availability-heading">Available now</h2>
                <span class="muted" style="font-size:13px">Live supply levels</span>
            </div>
            <?php $items = $stock; $wide = false; $linkToDonations = false; $bands = true; require BASE_PATH . '/views/partials/blood-grid.php'; ?>
            <p class="field-hint" style="margin-top:10px"><strong>Limited</strong> or <strong>Critical</strong>: still request what you need. The blood bank may send a compatible group or a partial amount.</p>
        </section>

        <div class="quick-actions">
            <button type="button" class="action-card" data-open-dialog="request-create">
                <span class="stat-icon tone-primary" style="background:var(--primary-soft);color:var(--primary)"><?= icon('clipboard') ?></span>
                <span><strong>Request blood</strong><span>Submit a new request</span></span>
            </button>
            <button type="button" class="action-card" data-open-dialog="donation-create">
                <span class="stat-icon" style="background:var(--success-soft);color:var(--success)"><?= icon('heart') ?></span>
                <span><strong>Log a donor</strong><span>Walk-in donation</span></span>
            </button>
        </div>
    </div>
</div>

<?php $stock = []; $hospitals = []; require BASE_PATH . '/views/partials/request-dialog.php'; ?>
<?php $dialogId = 'donation-create'; require BASE_PATH . '/views/partials/donation-dialog.php'; ?>
