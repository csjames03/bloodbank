<?php
/**
 * @var array{q: string, type: ?string, status: ?string} $filters
 * @var App\Core\Paginator $page
 * @var array<string, array{count: int, bags: int}> $summary
 * @var array<string, int> $stock
 * @var list<array<string, mixed>> $hospitals
 */
use App\Domain\BloodGroup;
use App\Domain\RequestStatus;

$admin = is_admin();
$all = array_sum(array_column($summary, 'count'));
$filtered = $filters['q'] !== '' || $filters['type'] || $filters['status'];
?>
<div class="page-head">
    <div>
        <h1><?= $admin ? 'Blood requests' : 'My requests' ?></h1>
        <p><?= $admin ? 'Approve, dispatch and track hospital requests.' : 'Follow every request from submission to delivery.' ?></p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" data-open-dialog="request-create"><?= icon('plus', 'icon-sm') ?> New request</button>
    </div>
</div>

<form method="get" action="<?= e(url('/requests')) ?>" class="toolbar" role="search" data-auto-submit>
    <?php if ($filters['status']): ?><input type="hidden" name="status" value="<?= e($filters['status']) ?>"><?php endif; ?>
    <label class="search">
        <span class="sr-only">Search requests</span>
        <?= icon('search') ?>
        <input class="input" type="search" name="q" value="<?= e($filters['q']) ?>"
               placeholder="<?= $admin ? 'Search doctor or hospital…' : 'Search by doctor…' ?>">
    </label>
    <label>
        <span class="sr-only">Blood type</span>
        <select class="select" name="type" style="min-width:140px">
            <option value="">All blood types</option>
            <?php foreach (BloodGroup::codes() as $code): ?>
                <option value="<?= e($code) ?>" <?= $filters['type'] === $code ? 'selected' : '' ?>><?= e($code) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <noscript><button class="btn" type="submit">Filter</button></noscript>
</form>

<div class="chips" style="margin-bottom:14px" aria-label="Filter by status">
    <a class="chip <?= $filters['status'] ? '' : 'is-active' ?>" href="<?= e(url_with(['status' => null, 'page' => null])) ?>">All <span class="n"><?= $all ?></span></a>
    <?php foreach (RequestStatus::cases() as $s): ?>
        <a class="chip <?= $filters['status'] === $s->value ? 'is-active' : '' ?>"
           href="<?= e(url_with(['status' => $s->value, 'page' => null])) ?>">
            <?= e($s->label()) ?> <span class="n"><?= $summary[$s->value]['count'] ?></span>
        </a>
    <?php endforeach; ?>
</div>

<section class="card">
    <?php if (!$page->items): ?>
        <div class="empty">
            <div class="empty-icon"><?= icon($filtered ? 'search' : 'clipboard', 'icon-lg') ?></div>
            <h3><?= $filtered ? 'No matching requests' : 'No requests yet' ?></h3>
            <p><?= $filtered ? 'Try a different search or clear the filters.' : 'New requests will appear here.' ?></p>
            <?php if ($filtered): ?>
                <a class="btn" style="margin-top:8px" href="<?= e(url('/requests')) ?>">Clear filters</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($page->items as $r): $showHospital = $admin; require BASE_PATH . '/views/partials/request-item.php'; endforeach; ?>
    <?php endif; ?>
    <?php require BASE_PATH . '/views/partials/pagination.php'; ?>
</section>

<?php require BASE_PATH . '/views/partials/request-dialog.php'; ?>
<?php if (is_admin()) { require BASE_PATH . '/views/partials/allocate-dialog.php'; } ?>
