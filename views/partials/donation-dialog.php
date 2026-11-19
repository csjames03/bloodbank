<?php
/**
 * Donation form modal — used for both "record" and "edit".
 *
 * @var string $dialogId  'donation-create' | 'donation-edit'
 */
use App\Core\Session;
use App\Domain\BloodGroup;

$isEdit = $dialogId === 'donation-edit';
$useOld = Session::getFlash('open_dialog') === $dialogId;
$o = static fn (string $k, string $d = '') => $useOld ? old($k, $d) : $d;
$err = static fn (string $k) => $useOld && error($k) ? 'has-error' : '';
$action = $isEdit ? url('/donations/' . (int) $o('_id', '0')) : url('/donations');
$p = $isEdit ? 'de' : 'dc'; // id prefix keeps label/for pairs unique per dialog
$maxBags = (int) config('inventory.max_donation_bags');
?>
<dialog class="dialog" id="<?= e($dialogId) ?>" aria-labelledby="<?= $p ?>-title">
    <div class="dialog-grip"></div>
    <form method="post" action="<?= e($action) ?>" class="dialog-body-form" novalidate
          <?= $isEdit ? 'data-edit-form data-action-base="' . e(url('/donations/')) . '"' : '' ?>>
        <?= csrf_field() ?>
        <?php if ($isEdit): ?><input type="hidden" name="_id" value="<?= e($o('_id')) ?>"><?php endif; ?>

        <div class="dialog-head">
            <div>
                <h2 id="<?= $p ?>-title"><?= $isEdit ? 'Edit donation' : 'Record a donation' ?></h2>
                <p><?= $isEdit ? 'Donor details can always be corrected. Blood type and bags can only change before screening.' : 'Each bag becomes a tracked unit, held in quarantine until it passes lab screening.' ?></p>
            </div>
            <button type="button" class="btn btn-ghost btn-icon btn-sm" data-close-dialog aria-label="Close"><?= icon('x') ?></button>
        </div>

        <div class="dialog-body">
            <?php if ($useOld && error('bags') && str_contains((string) error('bags'), 'already been screened')): ?>
                <div class="notice danger" style="margin-bottom:14px"><?= icon('alert', 'icon-sm') ?><span><?= e(error('bags')) ?></span></div>
            <?php endif; ?>

            <div class="form-grid">
                <span class="form-section">Donor</span>

                <div class="field col-2 <?= $err('first_name') ?>">
                    <label class="label" for="<?= $p ?>-first">First name</label>
                    <input class="input" id="<?= $p ?>-first" name="first_name" maxlength="60" required autocomplete="off" value="<?= e($o('first_name')) ?>">
                    <?= field_error('first_name', $useOld) ?>
                </div>
                <div class="field col-2 <?= $err('middle_name') ?>">
                    <label class="label" for="<?= $p ?>-middle">Middle name <span class="opt">(optional)</span></label>
                    <input class="input" id="<?= $p ?>-middle" name="middle_name" maxlength="60" autocomplete="off" value="<?= e($o('middle_name')) ?>">
                    <?= field_error('middle_name', $useOld) ?>
                </div>
                <div class="field col-2 <?= $err('last_name') ?>">
                    <label class="label" for="<?= $p ?>-last">Last name</label>
                    <input class="input" id="<?= $p ?>-last" name="last_name" maxlength="60" required autocomplete="off" value="<?= e($o('last_name')) ?>">
                    <?= field_error('last_name', $useOld) ?>
                </div>

                <div class="field col-3 <?= $err('sex') ?>">
                    <label class="label" for="<?= $p ?>-sex">Sex</label>
                    <select class="select" id="<?= $p ?>-sex" name="sex" required>
                        <?php foreach (['Female', 'Male'] as $sex): ?>
                            <option value="<?= $sex ?>" <?= $o('sex') === $sex ? 'selected' : '' ?>><?= $sex ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error('sex', $useOld) ?>
                </div>
                <div class="field col-3 <?= $err('age') ?>">
                    <label class="label" for="<?= $p ?>-age">Age</label>
                    <input class="input" id="<?= $p ?>-age" name="age" type="number" inputmode="numeric" min="16" max="65" required value="<?= e($o('age')) ?>">
                    <span class="field-hint">Eligible donors are 16–65.</span>
                    <?= field_error('age', $useOld) ?>
                </div>

                <div class="field col-3 <?= $err('contact') ?>">
                    <label class="label" for="<?= $p ?>-contact">Contact number</label>
                    <input class="input" id="<?= $p ?>-contact" name="contact" type="tel" inputmode="tel" maxlength="20" required placeholder="09XX XXX XXXX" value="<?= e($o('contact')) ?>">
                    <?= field_error('contact', $useOld) ?>
                </div>
                <div class="field col-3 <?= $err('address') ?>">
                    <label class="label" for="<?= $p ?>-address">Address</label>
                    <input class="input" id="<?= $p ?>-address" name="address" maxlength="255" required placeholder="City, Province" value="<?= e($o('address')) ?>">
                    <?= field_error('address', $useOld) ?>
                </div>

                <span class="form-section">Donation</span>

                <fieldset class="field col-6" style="border:0;padding:0">
                    <legend class="label" style="margin-bottom:6px">Blood type</legend>
                    <div class="type-picker">
                        <?php foreach (BloodGroup::codes() as $code): ?>
                            <label>
                                <input type="radio" name="blood_type" value="<?= e($code) ?>" <?= $o('blood_type', 'O+') === $code ? 'checked' : '' ?>>
                                <span><?= e($code) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?= field_error('blood_type', $useOld) ?>
                </fieldset>

                <div class="field col-2 <?= $err('bags') ?>">
                    <label class="label" for="<?= $p ?>-bags">Bags</label>
                    <input class="input" id="<?= $p ?>-bags" name="bags" type="number" inputmode="numeric" min="1" max="<?= $maxBags ?>" required value="<?= e($o('bags', '1')) ?>">
                    <?php if (!($useOld && str_contains((string) error('bags'), 'already been screened'))): ?><?= field_error('bags', $useOld) ?><?php endif; ?>
                </div>
                <div class="field col-4 <?= $err('medical_status') ?>">
                    <label class="label" for="<?= $p ?>-medical">Medical notes <span class="opt">(optional)</span></label>
                    <input class="input" id="<?= $p ?>-medical" name="medical_status" maxlength="255" placeholder="Fit to donate" value="<?= e($o('medical_status')) ?>">
                    <?= field_error('medical_status', $useOld) ?>
                </div>
            </div>
        </div>

        <div class="dialog-foot">
            <button type="button" class="btn" data-close-dialog>Cancel</button>
            <button type="submit" class="btn btn-primary"><?= icon($isEdit ? 'check' : 'heart', 'icon-sm') ?> <?= $isEdit ? 'Save changes' : 'Record donation' ?></button>
        </div>
    </form>
</dialog>
