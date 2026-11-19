<?php
use App\Core\Session;

$toast = Session::getFlash('toast');
?>
<div class="toasts" id="toasts" aria-live="polite">
    <?php if ($toast): ?>
        <div class="toast toast-<?= e($toast['type']) ?>" role="<?= $toast['type'] === 'error' ? 'alert' : 'status' ?>">
            <span class="toast-icon"><?= icon($toast['type'] === 'error' ? 'alert' : 'check') ?></span>
            <div class="toast-msg"><?= e($toast['message']) ?></div>
            <button type="button" class="toast-close" data-dismiss-toast aria-label="Dismiss"><?= icon('x', 'icon-sm') ?></button>
        </div>
    <?php endif; ?>
</div>
