<?php
/**
 * @var array{q: string, status: ?string, type: ?string} $filters
 * @var array<string, int> $counts
 * @var App\Core\Paginator $page
 */
use App\Domain\BloodGroup;
use App\Domain\DiscardReason;
use App\Domain\UnitStatus;

$warn = (int) config('inventory.expiry_warning_days');
$chips = [
    ''           => ['All', $counts['all'] ?? 0],
    'available'  => ['Available', $counts['available'] ?? 0],
    'expiring'   => ["Expiring ≤ {$warn}d", $counts['expiring'] ?? 0],
    'quarantine' => ['Awaiting screening', $counts['quarantine'] ?? 0],
    'dispatched' => ['Dispatched', $counts['dispatched'] ?? 0],
    'expired'    => ['Expired', $counts['expired'] ?? 0],
    'discarded'  => ['Discarded', $counts['discarded'] ?? 0],
];
$filtered = $filters['q'] !== '' || $filters['status'] || $filters['type'];
?>
<div class="page-head">
    <div>
        <h1>Blood units</h1>
        <p>Every bag, individually tracked from collection to dispatch. Dispatch always issues the soonest-expiring units first.</p>
    </div>
    <div class="page-actions">
        <a class="btn" href="<?= e(url('/screening')) ?>"><?= icon('shield', 'icon-sm') ?> Screening queue</a>
    </div>
</div>

<form method="get" action="<?= e(url('/units')) ?>" class="toolbar" role="search" data-auto-submit>
    <?php if ($filters['status']): ?><input type="hidden" name="status" value="<?= e($filters['status']) ?>"><?php endif; ?>
    <label class="search">
        <span class="sr-only">Search unit code</span>
        <?= icon('search') ?>
        <input class="input" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search unit code, e.g. LL000123…">
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
    <?php foreach ($chips as $value => [$label, $n]): ?>
        <a class="chip <?= ($filters['status'] ?? '') === $value ? 'is-active' : '' ?>"
           href="<?= e(url_with(['status' => $value ?: null, 'page' => null])) ?>"><?= e($label) ?> <span class="n"><?= (int) $n ?></span></a>
    <?php endforeach; ?>
</div>

<section class="card table-wrap">
    <?php if (!$page->items): ?>
        <div class="empty">
            <div class="empty-icon"><?= icon($filtered ? 'search' : 'droplet', 'icon-lg') ?></div>
            <h3><?= $filtered ? 'No matching units' : 'No units yet' ?></h3>
            <p><?= $filtered ? 'Try another filter or unit code.' : 'Units are created when a donation is recorded.' ?></p>
        </div>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Unit</th>
                    <th scope="col">Status</th>
                    <th scope="col">Collected</th>
                    <th scope="col">Expiry</th>
                    <th scope="col">Where / why</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($page->items as $u):
                    $status = UnitStatus::from($u['status']);
                    $live = in_array($status, [UnitStatus::Available, UnitStatus::Quarantine], true);
                ?>
                    <tr>
                        <td class="cell-full">
                            <div class="cell-person">
                                <span class="blood-code" style="min-width:40px;height:26px;font-size:13px"><?= e($u['blood_type']) ?></span>
                                <div class="list-main">
                                    <div class="list-title mono"><?= e($u['unit_code']) ?></div>
                                    <div class="list-sub">Donation #<?= (int) $u['donation_id'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Status"><span class="badge badge-<?= e($status->tone()) ?>"><?= e($status->label()) ?></span></td>
                        <td data-label="Collected"><?= e(fmt_date($u['collected_at'])) ?></td>
                        <td data-label="Expiry">
                            <?= e(fmt_date($u['expires_at'])) ?>
                            <?php if ($live): ?>
                                <div class="list-sub tone-text-<?= e(expiry_tone($u['expires_at'])) ?>"><?= e(expiry_label($u['expires_at'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td data-label="Where / why">
                            <?php if ($status === UnitStatus::Dispatched): ?>
                                <a class="link" href="<?= e(url('/requests')) ?>#request-<?= (int) $u['request_id'] ?>">Request #<?= (int) $u['request_id'] ?></a>
                                <div class="list-sub"><?= e($u['hospital_name']) ?> · <?= e(fmt_date($u['dispatched_at'], 'M j, g:i A')) ?></div>
                            <?php elseif ($status === UnitStatus::Discarded): ?>
                                <span class="list-sub" style="white-space:normal"><?= e($u['discard_reason']) ?></span>
                            <?php elseif ($status === UnitStatus::Quarantine): ?>
                                <a class="link" href="<?= e(url('/screening')) ?>#donation-<?= (int) $u['donation_id'] ?>">Awaiting lab</a>
                            <?php elseif ($status === UnitStatus::Available): ?>
                                <span class="list-sub">In storage<?= $u['screened_at'] ? ' · screened ' . e(fmt_date($u['screened_at'])) : '' ?></span>
                            <?php else: ?>
                                <span class="list-sub">Shelf life ran out</span>
                            <?php endif; ?>
                        </td>
                        <td class="cell-full">
                            <?php if ($status->canDiscard()): ?>
                                <details class="screen-fail row-actions">
                                    <summary class="btn btn-sm btn-danger"><?= icon('trash', 'icon-sm') ?> Discard</summary>
                                    <form method="post" action="<?= e(url("/units/{$u['id']}/discard")) ?>"
                                          data-confirm="Discard unit <?= e($u['unit_code']) ?>? It will be taken out of stock permanently."
                                          data-confirm-label="Discard unit" data-confirm-danger>
                                        <?= csrf_field() ?>
                                        <label class="label" for="dr-<?= (int) $u['id'] ?>">Reason</label>
                                        <select class="select select-sm" id="dr-<?= (int) $u['id'] ?>" name="reason" required>
                                            <?php foreach (DiscardReason::cases() as $r): ?>
                                                <option value="<?= e($r->value) ?>"><?= e($r->label()) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input class="input input-sm" name="note" maxlength="100" placeholder="Note (optional)" aria-label="Note">
                                        <button type="submit" class="btn btn-sm btn-danger">Discard</button>
                                    </form>
                                </details>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php require BASE_PATH . '/views/partials/pagination.php'; ?>
</section>
