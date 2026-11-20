<?php
/** @var string $portal  'hospital' | 'admin' */
$isAdmin = $portal === 'admin';
$action = $isAdmin ? '/admin/login' : '/login';
$authError = error('auth');
$demo = $isAdmin
    ? [['admin', 'admin123', 'Blood bank staff']]
    : [['riverside', 'hospital123', 'Riverside'], ['northpoint', 'hospital123', 'Northpoint'], ['staclara', 'hospital123', 'Sta. Clara']];
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

        <svg class="auth-pulse" viewBox="0 0 600 120" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0 60 H170 L190 60 L205 20 L222 100 L238 8 L254 86 L266 60 H600" />
        </svg>

        <div class="auth-pitch">
            <h2>Every bag tracked, from donor to bedside.</h2>
            <p>Screening, expiry, delivery and donor recall for the blood bank and its partner hospitals, in one place.</p>
            <ul class="auth-points">
                <li><span class="auth-point-icon"><?= icon('shield') ?></span><span><strong>Screened before stock</strong>Units count only once the lab clears them</span></li>
                <li><span class="auth-point-icon"><?= icon('clock') ?></span><span><strong>Oldest bags first</strong>Dispatch issues soonest-expiring units</span></li>
                <li><span class="auth-point-icon"><?= icon('activity') ?></span><span><strong>Days of supply</strong>Alerts and donor recall before a group runs out</span></li>
                <li><span class="auth-point-icon"><?= icon('truck') ?></span><span><strong>Live deliveries</strong>Every request tracked to the ward</span></li>
            </ul>
        </div>

        <p class="auth-foot">Secure portal for LifeLine staff and partner hospitals.</p>
    </aside>

    <main class="auth-main">
        <div class="auth-card">
            <nav class="auth-tabs" aria-label="Choose portal">
                <a href="<?= e(url('/login')) ?>" class="<?= $isAdmin ? '' : 'is-active' ?>" <?= $isAdmin ? '' : 'aria-current="page"' ?>><?= icon('hospital', 'icon-sm') ?> Hospital</a>
                <a href="<?= e(url('/admin/login')) ?>" class="<?= $isAdmin ? 'is-active' : '' ?>" <?= $isAdmin ? 'aria-current="page"' : '' ?>><?= icon('shield', 'icon-sm') ?> Blood bank staff</a>
            </nav>

            <div class="auth-head">
                <span class="auth-badge"><?= icon($isAdmin ? 'shield' : 'hospital') ?></span>
                <div>
                    <h1><?= $isAdmin ? 'Staff sign in' : 'Hospital sign in' ?></h1>
                    <p><?= $isAdmin
                        ? 'Manage screening, inventory and hospital requests.'
                        : 'Request blood and follow your deliveries.' ?></p>
                </div>
            </div>

            <?php if ($authError): ?>
                <div class="auth-alert" role="alert"><?= icon('alert', 'icon-sm') ?><span><?= e($authError) ?></span></div>
            <?php endif; ?>

            <form method="post" action="<?= e(url($action)) ?>" class="auth-form" novalidate>
                <?= csrf_field() ?>

                <div class="field <?= error('username') ? 'has-error' : '' ?>">
                    <label class="label" for="username">Username</label>
                    <div class="input-icon">
                        <?= icon('user') ?>
                        <input class="input" id="username" name="username" type="text" autocomplete="username"
                               autocapitalize="none" spellcheck="false" required <?= old('username') === '' ? 'autofocus' : '' ?>
                               value="<?= e(old('username')) ?>"
                               placeholder="<?= $isAdmin ? 'Your staff username' : 'Your hospital username' ?>"
                               <?= error('username') ? 'aria-invalid="true" aria-describedby="username-error"' : '' ?>>
                    </div>
                    <?php if (error('username')): ?><span class="field-error" id="username-error"><?= e(error('username')) ?></span><?php endif; ?>
                </div>

                <div class="field <?= error('password') ? 'has-error' : '' ?>">
                    <label class="label" for="password">Password</label>
                    <div class="input-icon password-wrap">
                        <?= icon('lock') ?>
                        <input class="input" id="password" name="password" type="password" autocomplete="current-password" required
                               <?= old('username') !== '' ? 'autofocus' : '' ?> aria-describedby="caps-hint">
                        <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password">
                            <?= icon('eye') ?>
                        </button>
                    </div>
                    <span class="caps-hint" id="caps-hint" data-caps-hint hidden><?= icon('alert', 'icon-sm') ?> Caps Lock is on</span>
                    <?php if (error('password')): ?><span class="field-error"><?= e(error('password')) ?></span><?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block auth-submit">
                    <span>Sign in</span> <?= icon('arrow-right', 'icon-sm') ?>
                </button>
            </form>

            <div class="auth-demo">
                <span class="auth-demo-label">Try a demo account</span>
                <div class="auth-demo-list">
                    <?php foreach ($demo as [$user, $pass, $label]): ?>
                        <button type="button" class="auth-demo-chip" data-demo-user="<?= e($user) ?>" data-demo-pass="<?= e($pass) ?>"
                                title="Fill in <?= e($user) ?> / <?= e($pass) ?>">
                            <strong><?= e($user) ?></strong><span><?= e($label) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <span class="auth-demo-pass">Password: <code><?= e($demo[0][1]) ?></code></span>
            </div>
        </div>

        <p class="auth-help"><?= $isAdmin ? 'Hospital user?' : 'Blood bank staff?' ?>
            <a class="link" href="<?= e(url($isAdmin ? '/login' : '/admin/login')) ?>"><?= $isAdmin ? 'Use the hospital portal' : 'Use the staff portal' ?> <?= icon('arrow-right', 'icon-sm') ?></a>
        </p>
    </main>
</div>
