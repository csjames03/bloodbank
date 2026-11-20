<?php
/**
 * @var string $group
 * @var list<array<string, mixed>> $forecast
 * @var array<string, mixed> $row          forecast row for $group
 * @var App\Core\Paginator $donors
 * @var array<string, int> $summary        outcome => count (cool-down window)
 */
use App\Domain\RecallOutcome;
use App\Services\SupplyService;

/** @var App\Domain\SupplyLevel $level */
$level = $row['level'];
$cooldown = (int) config('supply.recall_cooldown_days');
$interval = (int) config('inventory.donation_interval_days');
?>
<div class="page-head">
    <div>
        <h1>Donor recall</h1>
        <p>When a group runs short, call in donors who can give again: not deferred, and at least <?= $interval ?> days since their last donation.</p>
    </div>
    <div class="page-actions">
        <a class="btn" href="<?= e(url('/recall/export', ['group' => $group])) ?>"><?= icon('arrow-right', 'icon-sm') ?> Export <?= e($group) ?> call list (CSV)</a>
    </div>
</div>

<div class="supply-strip" role="tablist" aria-label="Blood group">
    <?php foreach ($forecast as $f): ?>
        <a href="<?= e(url('/recall', ['group' => $f['code']])) ?>" role="tab" aria-selected="<?= $f['code'] === $group ? 'true' : 'false' ?>"
           class="supply-pill level-<?= e($f['level']->value) ?> <?= $f['code'] === $group ? 'is-active' : '' ?>">
            <span class="supply-code"><?= e($f['code']) ?></span>
            <span class="supply-days"><?= e(SupplyService::formatDays($f['days'])) ?></span>
            <span class="supply-meta"><?= e($f['level']->label()) ?> · <?= plural($f['eligible'], 'donor') ?></span>
        </a>
    <?php endforeach; ?>
</div>

<section class="card section recall-summary level-<?= e($level->value) ?>">
    <div class="recall-figures">
        <div><span class="stat-label">Available</span><strong><?= (int) $row['available'] ?></strong><span class="muted">units of <?= e($group) ?></span></div>
        <div><span class="stat-label">Average use</span><strong><?= e(rtrim(rtrim(number_format($row['daily_use'], 2), '0'), '.') ?: '0') ?></strong><span class="muted">units / day (last <?= (int) config('supply.usage_window_days') ?> days)</span></div>
        <div><span class="stat-label">Days of supply</span><strong><?= e(SupplyService::formatDays($row['days'])) ?></strong><span class="badge badge-<?= e($level->tone()) ?>"><?= e($level->label()) ?></span></div>
        <div><span class="stat-label">Calls (<?= $cooldown ?> days)</span><strong><?= array_sum($summary) ?></strong>
            <span class="muted"><?= (int) ($summary['booked'] ?? 0) ?> booked · <?= (int) ($summary['no_answer'] ?? 0) ?> no answer</span></div>
    </div>
    <?php if ($level->needsRecall()): ?>
        <p class="recall-advice"><?= icon('alert', 'icon-sm') ?>
            <span><?= e($group) ?> is <strong><?= e(strtolower($level->label())) ?></strong>. Start with the donors at the top: they haven't been called recently and give most often.</span></p>
    <?php else: ?>
        <p class="recall-advice is-ok"><?= icon('check', 'icon-sm') ?><span><?= e($group) ?> supply is healthy. No recall needed right now.</span></p>
    <?php endif; ?>
</section>

<section class="card table-wrap section">
    <div class="card-head">
        <h2><?= plural($donors->total, 'eligible donor') ?> · <?= e($group) ?></h2>
        <span class="muted" style="font-size:13px">Not called in the last <?= $cooldown ?> days first</span>
    </div>
    <?php if (!$donors->items): ?>
        <div class="empty">
            <div class="empty-icon"><?= icon('users', 'icon-lg') ?></div>
            <h3>No eligible <?= e($group) ?> donors</h3>
            <p>Every <?= e($group) ?> donor has given in the last <?= $interval ?> days or is deferred. Consider a public appeal.</p>
        </div>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Donor</th>
                    <th scope="col">Contact</th>
                    <th scope="col">History</th>
                    <th scope="col">Last call</th>
                    <th scope="col"><span class="sr-only">Log call</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($donors->items as $d): ?>
                    <tr class="<?= $d['recently_contacted'] ? 'is-muted' : '' ?>">
                        <td class="cell-full">
                            <div class="cell-person">
                                <span class="avatar avatar-sm"><?= e(initials($d['first_name'] . ' ' . $d['last_name'])) ?></span>
                                <div class="list-main">
                                    <div class="list-title"><?= e($d['name']) ?></div>
                                    <div class="list-sub"><?= e($d['sex']) ?>, <?= (int) $d['age'] ?> · <?= e($d['address']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Contact"><a class="link mono" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $d['contact'])) ?>"><?= e($d['contact']) ?></a></td>
                        <td data-label="History">
                            <?= plural((int) $d['donations'], 'donation') ?>
                            <div class="list-sub"><?= $d['last_donated_at'] ? 'last ' . e(time_ago($d['last_donated_at'])) : 'never donated' ?></div>
                        </td>
                        <td data-label="Last call">
                            <?php if ($d['last_outcome']): $o = RecallOutcome::from($d['last_outcome']); ?>
                                <span class="badge badge-<?= e($o->tone()) ?>"><?= e($o->label()) ?></span>
                                <div class="list-sub"><?= e(time_ago($d['last_contacted_at'])) ?> · <?= e($d['last_contacted_by']) ?></div>
                            <?php else: ?>
                                <span class="list-sub">Not called yet</span>
                            <?php endif; ?>
                        </td>
                        <td class="cell-full">
                            <details class="screen-fail row-actions">
                                <summary class="btn btn-sm"><?= icon('pencil', 'icon-sm') ?> Log call</summary>
                                <form method="post" action="<?= e(url("/recall/{$d['id']}/contact")) ?>">
                                    <?= csrf_field() ?>
                                    <label class="label" for="oc-<?= (int) $d['id'] ?>">Outcome</label>
                                    <select class="select select-sm" id="oc-<?= (int) $d['id'] ?>" name="outcome" required>
                                        <?php foreach (RecallOutcome::cases() as $oc): ?>
                                            <option value="<?= e($oc->value) ?>"><?= e($oc->label()) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input class="input input-sm" name="note" maxlength="160" placeholder="Note, e.g. coming Saturday 9am" aria-label="Note">
                                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php $page = $donors; require BASE_PATH . '/views/partials/pagination.php'; ?>
</section>
