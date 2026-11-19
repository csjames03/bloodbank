<?php
/**
 * "Allocate & dispatch" modal for staff. Filled in by app.js from the
 * `data-allocate` JSON on the request card's approve form.
 */
?>
<dialog class="dialog" id="allocate-dialog" aria-labelledby="allocate-title">
    <div class="dialog-grip"></div>
    <form method="post" action="" class="dialog-body-form" data-allocate-form novalidate>
        <?= csrf_field() ?>
        <div class="dialog-head">
            <div>
                <h2 id="allocate-title">Allocate &amp; dispatch</h2>
                <p data-allocate-summary></p>
            </div>
            <button type="button" class="btn btn-ghost btn-icon btn-sm" data-close-dialog aria-label="Close"><?= icon('x') ?></button>
        </div>

        <div class="dialog-body">
            <p class="field-hint" style="margin-bottom:12px">
                Choose how many bags to send from each compatible group. The patient’s own group comes first and
                O− (the universal donor) last. Stock is deducted when you dispatch.
            </p>

            <div class="alloc-table" role="group" aria-label="Bags per blood group" data-allocate-rows></div>

            <div class="alloc-total">
                <span>Total to send</span>
                <strong data-allocate-total>0 / 0</strong>
            </div>

            <div class="notice warning" data-allocate-partial hidden>
                <?= icon('alert', 'icon-sm') ?><span>This is a <strong>partial fill</strong>. The hospital will be told how many bags are coming.</span>
            </div>
            <div class="notice danger" data-allocate-error hidden>
                <?= icon('alert', 'icon-sm') ?><span data-allocate-error-text></span>
            </div>
        </div>

        <div class="dialog-foot">
            <button type="button" class="btn" data-close-dialog>Cancel</button>
            <button type="submit" class="btn btn-primary" data-allocate-submit><?= icon('truck', 'icon-sm') ?> Dispatch</button>
        </div>
    </form>
</dialog>
