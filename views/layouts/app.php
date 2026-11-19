<?php
/** @var string $content */

use App\Core\Session;
use App\Repositories\NotificationRepository;
use App\Repositories\RequestRepository;
use App\Repositories\UnitRepository;

$user = auth_user();
$admin = is_admin();
$pendingCount = $admin ? (new RequestRepository())->summary()['pending']['count'] : 0;
$screeningCount = $admin ? (new UnitRepository())->quarantinedDonations() : 0;
$notifs = (new NotificationRepository())->counts($user['role'], (int) $user['id']);
$unreadLabel = $notifs['unread'] > 99 ? '99+' : (string) $notifs['unread'];

$nav = $admin
    ? [
        ['/', 'dashboard', 'Dashboard', 0],
        ['/requests', 'clipboard', 'Requests', $pendingCount],
        ['/screening', 'shield', 'Screening', $screeningCount],
        ['/donations', 'heart', 'Donations', 0],
        ['/stocks', 'package', 'Blood stock', 0],
        ['/units', 'droplet', 'Units', 0],
        ['/activity', 'activity', 'Activity', 0],
        ['/notifications', 'bell', 'Notifications', 0],
    ]
    : [
        ['/', 'dashboard', 'Dashboard', 0],
        ['/requests', 'clipboard', 'My requests', 0],
        ['/notifications', 'bell', 'Notifications', 0],
    ];
?>
<?php require BASE_PATH . '/views/partials/head.php'; ?>
<body data-reopen-dialog="<?= e(Session::getFlash('open_dialog', '')) ?>"
      data-notif-poll="<?= e(url('/notifications/poll')) ?>" data-notif-latest="<?= $notifs['latest'] ?>" data-notif-unread="<?= $notifs['unread'] ?>"
      data-notif-page="<?= e(url('/notifications')) ?>">
<?php require BASE_PATH . '/views/partials/icons.php'; ?>

<div class="shell">
    <aside class="sidebar" id="sidebar" aria-label="Main navigation">
        <a href="<?= e(url('/')) ?>" class="brand">
            <span class="brand-mark"><svg><use href="#i-drop-solid"/></svg></span>
            <span>
                <span class="brand-name">LifeLine</span><br>
                <span class="brand-sub">Blood Bank System</span>
            </span>
        </a>

        <nav class="nav">
            <span class="nav-label"><?= $admin ? 'Blood bank' : 'Hospital portal' ?></span>
            <?php foreach ($nav as [$path, $ico, $label, $count]): ?>
                <a href="<?= e(url($path)) ?>" class="nav-link <?= is_active($path) ? 'is-active' : '' ?>"
                   <?= is_active($path) ? 'aria-current="page"' : '' ?>>
                    <?= icon($ico) ?>
                    <span><?= e($label) ?></span>
                    <?php if ($path === '/notifications'): ?>
                        <span class="count" data-notif-count title="Unread notifications" <?= $notifs['unread'] ? '' : 'hidden' ?>><?= $unreadLabel ?></span>
                    <?php elseif ($admin && $path === '/requests'): ?>
                        <span class="count" data-pending-count title="Pending requests" <?= $count ? '' : 'hidden' ?>><?= $count ?></span>
                    <?php elseif ($count > 0): ?>
                        <span class="count count-soft" title="Donations awaiting screening"><?= $count ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-foot">
            <div class="user-chip">
                <span class="avatar"><?= e(initials($user['name'])) ?></span>
                <span class="who">
                    <span class="name" title="<?= e($user['name']) ?>"><?= e($user['name']) ?></span>
                    <span class="role"><?= $admin ? 'Administrator' : 'Partner hospital' ?></span>
                </span>
            </div>
            <form method="post" action="<?= e(url('/logout')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="nav-link btn-block" style="border:0;background:none;">
                    <?= icon('logout') ?><span>Sign out</span>
                </button>
            </form>
        </div>
    </aside>
    <div class="backdrop" id="backdrop" data-close-sidebar></div>

    <div class="content">
        <header class="topbar">
            <button type="button" class="btn btn-ghost btn-icon" data-open-sidebar aria-label="Open menu" aria-controls="sidebar" aria-expanded="false">
                <?= icon('menu') ?>
            </button>
            <a href="<?= e(url('/')) ?>" class="brand">
                <span class="brand-mark"><svg><use href="#i-drop-solid"/></svg></span>
                <span class="brand-name">LifeLine</span>
            </a>
            <a href="<?= e(url('/notifications')) ?>" class="btn btn-ghost btn-icon bell" style="margin-left:auto"
               aria-label="Notifications<?= $notifs['unread'] ? ', ' . $notifs['unread'] . ' unread' : '' ?>" data-notif-bell>
                <?= icon('bell') ?>
                <span class="bell-badge" data-notif-count <?= $notifs['unread'] ? '' : 'hidden' ?>><?= $unreadLabel ?></span>
            </a>
            <span class="avatar avatar-sm" title="<?= e($user['name']) ?>"><?= e(initials($user['name'])) ?></span>
        </header>

        <main class="main" id="main">
            <?= $content ?>
        </main>
    </div>
</div>

<?php require BASE_PATH . '/views/partials/toasts.php'; ?>
<?php require BASE_PATH . '/views/partials/confirm.php'; ?>
</body>
</html>
