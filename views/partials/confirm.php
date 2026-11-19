<?php /* Shared confirmation dialog, driven by `data-confirm` on any form (see app.js). */ ?>
<dialog class="dialog dialog-sm" id="confirm-dialog" aria-labelledby="confirm-title">
    <div class="dialog-grip"></div>
    <div class="dialog-head">
        <div>
            <h2 id="confirm-title">Are you sure?</h2>
            <p id="confirm-message"></p>
        </div>
    </div>
    <div class="dialog-body"></div>
    <div class="dialog-foot">
        <button type="button" class="btn" data-confirm-cancel>Keep it</button>
        <button type="button" class="btn btn-primary" data-confirm-ok>Confirm</button>
    </div>
</dialog>
