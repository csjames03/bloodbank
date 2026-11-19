<?php
/** @var string $portal  'hospital' | 'admin' */
$isAdmin = $portal === 'admin';
$action = $isAdmin ? '/admin/login' : '/login';
?>
<div class="auth">
    <aside class="auth-aside">
        <div class="brand">
            <span class="brand-mark"><svg><use href="#i-drop-solid"/></svg></span>
            <span>
                <span class="brand-name">LifeLine</span><br>
                <span class="brand-sub">Blood Bank System</span>
            </span>
        </div>

        <div class="auth-pitch">
            <h2>Every bag accounted for, every request on its way.</h2>
            <p>Track donations, live blood inventory and hospital deliveries in one place.</p>
            <ul class="auth-points">
                <li><?= icon('droplet') ?> Real-time stock for all eight blood groups</li>
                <li><?= icon('truck') ?> Delivery tracking from request to hand-off</li>
                <li><?= icon('shield') ?> Compatibility hints when a group runs low</li>
            </ul>
        </div>

        <svg class="auth-drop" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-drop-solid"/></svg>
    </aside>

    <main class="auth-main">
        <div class="auth-card">
            <nav class="auth-tabs" aria-label="Choose portal">
                <a href="<?= e(url('/login')) ?>" class="<?= $isAdmin ? '' : 'is-active' ?>">Hospital</a>
                <a href="<?= e(url('/admin/login')) ?>" class="<?= $isAdmin ? 'is-active' : '' ?>">Blood bank staff</a>
            </nav>

            <h1><?= $isAdmin ? 'Staff sign in' : 'Hospital sign in' ?></h1>
            <p><?= $isAdmin
                ? 'Manage inventory, donations and hospital requests.'
                : 'Request blood and follow your deliveries.' ?></p>

            <form method="post" action="<?= e(url($action)) ?>" class="auth-form" novalidate>
                <?= csrf_field() ?>

                <div class="field <?= error('username') ? 'has-error' : '' ?>">
                    <label class="label" for="username">Username</label>
                    <input class="input" id="username" name="username" type="text" autocomplete="username"
                           autocapitalize="none" spellcheck="false" required autofocus
                           value="<?= e(old('username')) ?>"
                           placeholder="<?= $isAdmin ? 'admin' : 'e.g. riverside' ?>"
                           <?= error('username') ? 'aria-invalid="true" aria-describedby="username-error"' : '' ?>>
                    <?php if (error('username')): ?><span class="field-error" id="username-error"><?= e(error('username')) ?></span><?php endif; ?>
                </div>

                <div class="field <?= error('password') ? 'has-error' : '' ?>">
                    <label class="label" for="password">Password</label>
                    <div class="password-wrap">
                        <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password">
                            <?= icon('eye') ?>
                        </button>
                    </div>
                    <?php if (error('password')): ?><span class="field-error"><?= e(error('password')) ?></span><?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="min-height:48px">
                    Sign in <?= icon('arrow-right', 'icon-sm') ?>
                </button>
            </form>

            <div class="demo">
                <strong>Demo account:</strong>
                <?php if ($isAdmin): ?>
                    <code>admin</code> / <code>admin123</code>
                <?php else: ?>
                    <code>riverside</code>, <code>northpoint</code> or <code>staclara</code> / <code>hospital123</code>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
