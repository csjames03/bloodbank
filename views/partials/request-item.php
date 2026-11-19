<?php
/**
 * One request with its delivery tracker.
 *
 * @var array<string, mixed> $r      row from RequestRepository (with allocation/sent/actors)
 * @var bool $showHospital
 * @var array<string, int> $stock    code => bags (used for staff allocation)
 */
use App\Domain\BloodGroup;
use App\Domain\Priority;
use App\Domain\RequestStatus;
use App\Services\RequestService;

$status = RequestStatus::from($r['status']);
$priority = Priority::from($r['priority']);
$step = $status->step();
$admin = is_admin();
$canHospitalCancel = !$admin && $status === RequestStatus::Pending;
$canHospitalReceive = !$admin && $status === RequestStatus::InTransit;
$actors = $r['actors'] ?? [];
$allocation = $r['allocation'] ?? [];
$sent = (int) ($r['sent'] ?? 0);
$substituted = array_diff(array_keys($allocation), [$r['blood_type']]) !== [];
$overdue = $status->isOpen() && $r['needed_by'] && new DateTimeImmutable($r['needed_by']) < new DateTimeImmutable();

// Staff allocation dialog data: every compatible group with stock and the suggested split.
$allocateData = null;
if ($admin && $status === RequestStatus::Pending) {
    $stockMap = is_array($stock ?? null) ? $stock : [];
    $expiryMap = is_array($expiry ?? null) ? $expiry : [];
    $suggested = RequestService::suggest($r, $stockMap);
    $options = array_map(static fn (BloodGroup $g) => [
        'code'      => $g->value,
        'stock'     => (int) ($stockMap[$g->value] ?? 0),
        'suggested' => $suggested[$g->value] ?? 0,
        'exact'     => $g->value === $r['blood_type'],
        'expiry'    => isset($expiryMap[$g->value]) ? 'oldest ' . expiry_label($expiryMap[$g->value]) : null,
    ], BloodGroup::from($r['blood_type'])->preferredDonors($stockMap));

    $allocateData = [
        'id'       => (int) $r['id'],
        'action'   => url("/requests/{$r['id']}/approve"),
        'type'     => $r['blood_type'],
        'bags'     => (int) $r['bags'],
        'hospital' => $r['hospital_name'],
        'doctor'   => $r['doctor_name'],
        'priority' => $priority->value,
        'options'  => $options,
    ];
}
?>
<article class="request-card <?= $status === RequestStatus::Pending && $priority === Priority::Emergency ? 'is-emergency' : '' ?>" id="request-<?= (int) $r['id'] ?>">
    <div class="request-head">
        <span class="blood-code"><?= e($r['blood_type']) ?></span>
        <div class="list-main">
            <div class="list-title">
                <?= plural((int) $r['bags'], 'bag') ?>
                <?php if ($showHospital): ?> · <?= e($r['hospital_name']) ?><?php endif; ?>
            </div>
            <div class="list-sub"><?= e($r['doctor_name']) ?> · <?= e(time_ago($r['requested_at'])) ?></div>
        </div>
        <div class="request-badges">
            <?php if ($priority !== Priority::Routine): ?>
                <span class="badge badge-<?= e($priority->tone()) ?>"><?= e($priority->label()) ?></span>
            <?php endif; ?>
            <?= status_badge($r['status']) ?>
        </div>
    </div>

    <?php if ($r['needed_by'] && $status !== RequestStatus::Cancelled): ?>
        <div class="request-meta <?= $overdue ? 'is-overdue' : '' ?>">
            <?= icon('clock', 'icon-sm') ?>
            <span>Needed by <strong><?= e(fmt_date($r['needed_by'], 'M j, g:i A')) ?></strong><?= $overdue ? ' · overdue' : '' ?></span>
        </div>
    <?php endif; ?>

    <?php if ($allocation !== [] && $status !== RequestStatus::Cancelled): ?>
        <div class="request-meta">
            <?= icon('package', 'icon-sm') ?>
            <span>
                Sent
                <?php foreach ($allocation as $code => $bags): ?>
                    <span class="alloc-chip <?= $code !== $r['blood_type'] ? 'is-sub' : '' ?>"><?= (int) $bags ?> × <?= e($code) ?></span>
                <?php endforeach; ?>
                <?php if ($substituted): ?><span class="muted">· compatible substitute</span><?php endif; ?>
                <?php if ($sent < (int) $r['bags']): ?><span class="badge badge-warning plain">Partial <?= $sent ?>/<?= (int) $r['bags'] ?></span><?php endif; ?>
            </span>
        </div>
        <?php if (!empty($r['units'])): ?>
            <details class="unit-trace">
                <summary><?= plural(count($r['units']), 'unit') ?> · use by <?= e(fmt_date($r['units'][0]['expires_at'], 'M j')) ?></summary>
                <ul>
                    <?php foreach ($r['units'] as $u): ?>
                        <li><span class="mono"><?= e($u['unit_code']) ?></span> <span class="alloc-chip"><?= e($u['blood_type']) ?></span> <span class="muted">use by <?= e(fmt_date($u['expires_at'], 'M j, Y')) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($status === RequestStatus::Cancelled): ?>
        <div class="tracker-cancelled">
            <?= icon('ban', 'icon-sm') ?> Cancelled <?= e(time_ago($r['cancelled_at'])) ?>
            <?php if (isset($actors['cancelled'])): ?> by <?= e($actors['cancelled']) ?><?php endif; ?>
        </div>
    <?php else: ?>
        <div class="tracker" data-step="<?= $step ?>" role="img"
             aria-label="Delivery progress: <?= e($status->label()) ?>">
            <span class="fill"></span>
            <?php
            $steps = [
                [1, 'Requested', 'clipboard', $r['requested_at'], $actors['created'] ?? null],
                [2, 'Dispatched', 'truck', $r['approved_at'], $actors['approved'] ?? null],
                [3, 'Received', 'check', $r['completed_at'], $actors['received'] ?? null],
            ];
            foreach ($steps as [$n, $label, $ico, $at, $by]):
                $cls = $step > $n || $step === 3 ? 'is-done' : ($step === $n ? 'is-current' : '');
            ?>
                <div class="step <?= $cls ?>">
                    <span class="step-dot <?= $n === 2 && $step === 2 ? 'truck' : '' ?>"><?= icon($ico) ?></span>
                    <span class="step-label"><?= $label ?></span>
                    <span class="step-time"><?= $at ? e(fmt_date($at, 'M j, g:i A')) : '&nbsp;' ?></span>
                    <?php if ($at && $by): ?><span class="step-by" title="<?= e($by) ?>">by <?= e($by) ?></span><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (($admin && $status->isOpen()) || $canHospitalCancel || $canHospitalReceive): ?>
        <div class="row" style="justify-content:flex-end">
            <?php if ($allocateData !== null): ?>
                <form method="post" action="<?= e($allocateData['action']) ?>"
                      data-allocate='<?= e(json_encode($allocateData, JSON_UNESCAPED_UNICODE)) ?>'>
                    <?= csrf_field() ?>
                    <?php /* No-JS fallback: dispatch the suggested allocation. */ ?>
                    <?php foreach ($suggested as $code => $bags): ?>
                        <input type="hidden" name="alloc[<?= e($code) ?>]" value="<?= (int) $bags ?>">
                    <?php endforeach; ?>
                    <button class="btn btn-sm btn-primary" type="submit" <?= $suggested === [] ? 'disabled title="No compatible stock"' : '' ?>>
                        <?= icon('truck', 'icon-sm') ?> Allocate &amp; dispatch
                    </button>
                </form>
            <?php elseif ($canHospitalReceive): ?>
                <form method="post" action="<?= e(url("/requests/{$r['id']}/receive")) ?>"
                      data-confirm="Confirm you received <?= e(plural($sent ?: (int) $r['bags'], 'bag')) ?> for <?= e($r['doctor_name']) ?>?" data-confirm-label="Confirm receipt">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-success" type="submit"><?= icon('check', 'icon-sm') ?> Confirm receipt</button>
                </form>
            <?php elseif ($admin && $status === RequestStatus::InTransit): ?>
                <form method="post" action="<?= e(url("/requests/{$r['id']}/receive")) ?>"
                      data-confirm="Record that <?= e($r['hospital_name']) ?> received the blood? Normally the hospital confirms this themselves; only do it if they confirmed by phone."
                      data-confirm-label="Record as received">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm" type="submit"><?= icon('check', 'icon-sm') ?> Record receipt</button>
                </form>
            <?php endif; ?>

            <?php if (($admin && $status->isOpen()) || $canHospitalCancel): ?>
                <form method="post" action="<?= e(url("/requests/{$r['id']}/cancel")) ?>"
                      data-confirm="Cancel this request?<?= $status === RequestStatus::InTransit ? ' The dispatched bags will be returned to stock.' : '' ?>"
                      data-confirm-label="Cancel request" data-confirm-danger>
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-danger" type="submit"><?= icon('x', 'icon-sm') ?> Cancel</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</article>
