<?php
/** @var int $status @var string $message @var string|null $detail */
?>
<main class="error-page">
    <div>
        <div class="error-code"><?= (int) $status ?></div>
        <h1 style="margin-top:12px;font-size:24px"><?= $status === 404 ? 'We couldn’t find that page' : ($status === 403 ? 'Access denied' : 'Something went wrong') ?></h1>
        <p class="muted" style="margin-top:8px"><?= e($message) ?></p>
        <div class="row" style="justify-content:center;margin-top:24px">
            <a href="<?= e(url('/')) ?>" class="btn btn-primary"><?= icon('dashboard', 'icon-sm') ?> Back to dashboard</a>
        </div>
        <?php if (!empty($detail)): ?>
            <pre><?= e($detail) ?></pre>
        <?php endif; ?>
    </div>
</main>
