<?php
/**
 * @var array{subject: ?string, actor: string} $filters
 * @var App\Core\Paginator $page
 */
$verbs = [
    'request'  => ['created' => 'submitted', 'approved' => 'dispatched', 'received' => 'confirmed receipt of', 'cancelled' => 'cancelled'],
    'donation' => ['recorded' => 'recorded', 'updated' => 'edited', 'deleted' => 'deleted'],
];
$icons = ['created' => 'clipboard', 'approved' => 'truck', 'received' => 'check', 'cancelled' => 'ban', 'recorded' => 'heart', 'updated' => 'pencil', 'deleted' => 'trash'];
$tones = ['created' => 'info', 'approved' => 'primary', 'received' => 'success', 'cancelled' => 'neutral', 'recorded' => 'success', 'updated' => 'warning', 'deleted' => 'danger'];
?>
<div class="page-head">
    <div>
        <h1>Activity</h1>
        <p>Every action on requests and donations, with who did it and when.</p>
    </div>
</div>

<form method="get" action="<?= e(url('/activity')) ?>" class="toolbar" role="search" data-auto-submit>
    <?php if ($filters['subject']): ?><input type="hidden" name="subject" value="<?= e($filters['subject']) ?>"><?php endif; ?>
    <label class="search">
        <span class="sr-only">Filter by person or hospital</span>
        <?= icon('search') ?>
        <input class="input" type="search" name="actor" value="<?= e($filters['actor']) ?>" placeholder="Filter by staff member or hospital…">
    </label>
    <noscript><button class="btn" type="submit">Filter</button></noscript>
</form>

<div class="chips" style="margin-bottom:14px" aria-label="Filter by record type">
    <?php foreach (['' => 'Everything', 'request' => 'Requests', 'donation' => 'Donations'] as $value => $label): ?>
        <a class="chip <?= $filters['subject'] === ($value ?: null) ? 'is-active' : '' ?>"
           href="<?= e(url_with(['subject' => $value ?: null, 'page' => null])) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<section class="card">
    <?php if (!$page->items): ?>
        <div class="empty">
            <div class="empty-icon"><?= icon('activity', 'icon-lg') ?></div>
            <h3>No activity found</h3>
            <p>Actions on requests and donations will appear here.</p>
        </div>
    <?php else: ?>
        <ul class="notif-list">
            <?php foreach ($page->items as $a):
                $verb = $verbs[$a['subject']][$a['action']] ?? $a['action'];
            ?>
                <li class="notif activity-row">
                    <span class="notif-icon tone-<?= e($tones[$a['action']] ?? 'neutral') ?>"><?= icon($icons[$a['action']] ?? 'activity') ?></span>
                    <span class="notif-main">
                        <span class="notif-title">
                            <strong><?= e($a['actor_name']) ?></strong>
                            <span class="muted"><?= $a['actor_role'] === 'hospital' ? '(hospital)' : '' ?></span>
                            <?= e($verb) ?> <?= e($a['subject']) ?> #<?= (int) $a['subject_id'] ?>
                        </span>
                        <?php if ($a['note'] !== ''): ?><span class="notif-body"><?= e($a['note']) ?></span><?php endif; ?>
                        <span class="notif-time" title="<?= e(fmt_datetime($a['created_at'])) ?>"><?= e(fmt_datetime($a['created_at'])) ?> · <?= e(time_ago($a['created_at'])) ?></span>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php require BASE_PATH . '/views/partials/pagination.php'; ?>
    <?php endif; ?>
</section>
