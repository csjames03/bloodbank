<?php
/**
 * "Request blood" modal.
 *
 * @var array<string, int> $stock     code => available bags
 * @var list<array<string, mixed>> $hospitals  (admin only)
 */
use App\Core\Session;
use App\Domain\BloodGroup;
use App\Domain\Priority;

$useOld = Session::getFlash('open_dialog') === 'request-create';
$o = static fn (string $k, string $d = '') => $useOld ? old($k, $d) : $d;
$compat = [];
foreach (BloodGroup::cases() as $g) {
    $compat[$g->value] = array_map(static fn (BloodGroup $d) => $d->value, $g->canReceiveFrom());
}
$selected = $o('blood_type', 'A+');
?>
<dialog class="dialog" id="request-create" aria-labelledby="request-create-title">
    <div class="dialog-grip"></div>
    <form method="post" action="<?= e(url('/requests')) ?>" class="dialog-body-form" novalidate
          data-request-form data-stock='<?= e(json_encode($stock)) ?>' data-compat='<?= e(json_encode($compat)) ?>'>
        <?= csrf_field() ?>
        <div class="dialog-head">
            <div>
                <h2 id="request-create-title">Request blood</h2>
                <p>Ask for what the patient needs. If a group is short, the blood bank can send a compatible one.</p>
            </div>
            <button type="button" class="btn btn-ghost btn-icon btn-sm" data-close-dialog aria-label="Close"><?= icon('x') ?></button>
        </div>

        <div class="dialog-body">
            <div class="form-grid">
                <?php if (is_admin()): ?>
                    <div class="field col-6 <?= $useOld && error('hospital_id') ? 'has-error' : '' ?>">
                        <label class="label" for="rq-hospital">Hospital</label>
                        <select class="select" id="rq-hospital" name="hospital_id" required>
                            <option value="">Select a hospital…</option>
                            <?php foreach ($hospitals as $h): ?>
                                <option value="<?= (int) $h['id'] ?>" <?= $o('hospital_id') === (string) $h['id'] ? 'selected' : '' ?>><?= e($h['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error('hospital_id', $useOld) ?>
                    </div>
                <?php endif; ?>

                <div class="field col-6 <?= $useOld && error('doctor_name') ? 'has-error' : '' ?>">
                    <label class="label" for="rq-doctor">Requesting doctor</label>
                    <input class="input" id="rq-doctor" name="doctor_name" type="text" maxlength="100" required
                           placeholder="e.g. Maria Santos" autocomplete="off" value="<?= e($o('doctor_name')) ?>">
                    <span class="field-hint">“Dr.” is added automatically.</span>
                    <?= field_error('doctor_name', $useOld) ?>
                </div>

                <fieldset class="field col-6" style="border:0;padding:0">
                    <legend class="label" style="margin-bottom:6px">Blood type <span class="opt">· bags in stock</span></legend>
                    <div class="type-picker">
                        <?php foreach (BloodGroup::codes() as $code): ?>
                            <label>
                                <input type="radio" name="blood_type" value="<?= e($code) ?>" <?= $selected === $code ? 'checked' : '' ?>>
                                <span><?= e($code) ?><small><?= (int) ($stock[$code] ?? 0) ?> in stock</small></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?= field_error('blood_type', $useOld) ?>
                </fieldset>

                <div class="field col-3 <?= $useOld && error('bags') ? 'has-error' : '' ?>">
                    <label class="label" for="rq-bags">Number of bags</label>
                    <input class="input" id="rq-bags" name="bags" type="number" inputmode="numeric" min="1"
                           max="<?= (int) config('inventory.max_request_bags') ?>" required value="<?= e($o('bags', '1')) ?>">
                </div>

                <div class="field col-3 <?= $useOld && error('needed_by') ? 'has-error' : '' ?>">
                    <label class="label" for="rq-needed">Needed by <span class="opt">(optional)</span></label>
                    <input class="input" id="rq-needed" name="needed_by" type="datetime-local"
                           min="<?= e(date('Y-m-d\TH:i')) ?>" value="<?= e($o('needed_by')) ?>">
                    <?= field_error('needed_by', $useOld) ?>
                </div>

                <fieldset class="field col-6" style="border:0;padding:0">
                    <legend class="label" style="margin-bottom:6px">Priority</legend>
                    <div class="priority-picker">
                        <?php foreach (Priority::cases() as $p): ?>
                            <label class="tone-<?= e($p->tone()) ?>">
                                <input type="radio" name="priority" value="<?= e($p->value) ?>" <?= $o('priority', 'routine') === $p->value ? 'checked' : '' ?>>
                                <span><strong><?= e($p->label()) ?></strong><small><?= e($p->hint()) ?></small></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?= field_error('priority', $useOld) ?>
                </fieldset>

                <div class="col-6">
                    <div class="notice info" data-stock-notice hidden>
                        <?= icon('alert', 'icon-sm') ?><span data-stock-message></span>
                    </div>
                    <?php if ($useOld && error('bags')): ?>
                        <div class="notice danger" style="margin-top:8px"><?= icon('alert', 'icon-sm') ?><span><?= e(error('bags')) ?></span></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="dialog-foot">
            <button type="button" class="btn" data-close-dialog>Cancel</button>
            <button type="submit" class="btn btn-primary"><?= icon('clipboard', 'icon-sm') ?> Submit request</button>
        </div>
    </form>
</dialog>
