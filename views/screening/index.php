<?php
/**
 * @var list<array<string, mixed>> $queue
 */
use App\Domain\BloodGroup;
use App\Domain\ScreeningFailure;
?>
<div class="page-head">
    <div>
        <h1>Lab screening</h1>
        <p>New donations wait here in quarantine. They only count as stock once they pass screening and their blood group is confirmed.</p>
    </div>
</div>

<div class="notice" style="margin-bottom:16px">
    <?= icon('shield', 'icon-sm') ?>
    <span>Each donation is tested for HIV, hepatitis B and C, syphilis and malaria, and its ABO/Rh group is confirmed.
        <strong>Pass</strong> releases its units to stock, under the confirmed group. <strong>Fail</strong> discards them and defers the donor
        (permanently for HIV and hepatitis, 12 months for syphilis and malaria).</span>
</div>

<section class="card">
    <?php if (!$queue): ?>
        <div class="empty">
            <div class="empty-icon"><?= icon('check', 'icon-lg') ?></div>
            <h3>Nothing awaiting screening</h3>
            <p>New donations appear here as soon as they are recorded.</p>
        </div>
    <?php else: ?>
        <div class="card-head">
            <h2><?= plural(count($queue), 'donation') ?> in quarantine</h2>
            <span class="muted" style="font-size:13px">Oldest first</span>
        </div>
        <ul class="list">
            <?php foreach ($queue as $q): ?>
                <li class="screen-item" id="donation-<?= (int) $q['donation_id'] ?>">
                    <div class="request-head">
                        <span class="blood-code"><?= e($q['blood_type']) ?></span>
                        <div class="list-main">
                            <div class="list-title"><?= e($q['donor_name']) ?></div>
                            <div class="list-sub">
                                <?= e($q['sex']) ?>, <?= (int) $q['age'] ?> ·
                                collected <?= e(time_ago($q['donated_at'])) ?>
                                <?= $q['hospital_name'] ? ' via ' . e($q['hospital_name']) : ' at the blood bank' ?>
                            </div>
                        </div>
                        <span class="badge badge-<?= e(expiry_tone($q['expires_at'])) ?> plain"><?= e(expiry_label($q['expires_at'])) ?></span>
                    </div>
                    <div class="request-meta">
                        <?= icon('droplet', 'icon-sm') ?>
                        <span><?= plural((int) $q['units'], 'unit') ?>: <span class="mono"><?= e($q['unit_codes']) ?></span></span>
                    </div>
                    <?php if ($q['medical_status']): ?>
                        <div class="request-meta"><?= icon('clipboard', 'icon-sm') ?><span><?= e($q['medical_status']) ?></span></div>
                    <?php endif; ?>

                    <div class="screen-actions">
                        <form method="post" action="<?= e(url("/screening/{$q['donation_id']}/pass")) ?>" class="screen-pass">
                            <?= csrf_field() ?>
                            <label class="sr-only" for="grp-<?= (int) $q['donation_id'] ?>">Confirmed blood group</label>
                            <select class="select select-sm" id="grp-<?= (int) $q['donation_id'] ?>" name="confirmed_group" title="Blood group confirmed by the lab">
                                <?php foreach (BloodGroup::codes() as $code): ?>
                                    <option value="<?= e($code) ?>" <?= $code === $q['blood_type'] ? 'selected' : '' ?>>Confirmed <?= e($code) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-success"><?= icon('check', 'icon-sm') ?> Pass &amp; release</button>
                        </form>

                        <details class="screen-fail">
                            <summary class="btn btn-sm btn-danger"><?= icon('x', 'icon-sm') ?> Fail</summary>
                            <form method="post" action="<?= e(url("/screening/{$q['donation_id']}/fail")) ?>"
                                  data-confirm="Record this donation as failed? Its <?= e(plural((int) $q['units'], 'unit')) ?> will be discarded and the donor may be deferred."
                                  data-confirm-label="Record failure" data-confirm-danger>
                                <?= csrf_field() ?>
                                <label class="label" for="why-<?= (int) $q['donation_id'] ?>">Reason</label>
                                <select class="select select-sm" id="why-<?= (int) $q['donation_id'] ?>" name="reason" required>
                                    <?php foreach (ScreeningFailure::cases() as $f): ?>
                                        <option value="<?= e($f->value) ?>"><?= e($f->label()) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-danger">Record failure</button>
                            </form>
                        </details>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
