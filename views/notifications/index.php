<?php
/**
 * @var bool $unreadOnly
 * @var array{unread: int, total: int, latest: int} $counts
 * @var App\Core\Paginator $page
 */
use App\Domain\NotificationType;
?>
<div class="page-head">
    <div>
        <h1>Notifications</h1>
        <p><?= is_admin()
            ? 'New requests, withdrawals, walk-in donors and stock alerts.'
            : 'Updates on your requests from the blood bank.' ?></p>
    </div>
    <?php if ($counts['unread'] > 0): ?>
        <div class="page-actions">
            <form method="post" action="<?= e(url('/notifications/read-all')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn"><?= icon('check-check', 'icon-sm') ?> Mark all as read</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<div class="chips" style="margin-bottom:14px" aria-label="Filter notifications">
    <a class="chip <?= $unreadOnly ? '' : 'is-active' ?>" href="<?= e(url('/notifications')) ?>"
       <?= $unreadOnly ? '' : 'aria-current="page"' ?>>All <span class="n"><?= $counts['total'] ?></span></a>
    <a class="chip <?= $unreadOnly ? 'is-active' : '' ?>" href="<?= e(url('/notifications', ['filter' => 'unread'])) ?>"
       <?= $unreadOnly ? 'aria-current="page"' : '' ?>>Unread <span class="n" data-notif-count-plain><?= $counts['unread'] ?></span></a>
</div>

<section class="card">
    <?php if (!$page->items): ?>
        <div class="empty">
            <div class="empty-icon"><?= icon('bell', 'icon-lg') ?></div>
            <h3><?= $unreadOnly ? 'You’re all caught up' : 'No notifications yet' ?></h3>
            <p><?= $unreadOnly
                ? 'Nothing new since you last checked.'
                : (is_admin() ? 'You’ll be notified here when hospitals request blood or stock runs low.' : 'You’ll be notified here when your requests are dispatched, delivered or cancelled.') ?></p>
        </div>
    <?php else: ?>
        <ul class="notif-list">
            <?php foreach ($page->items as $n): $type = NotificationType::present($n['type']); $unread = $n['read_at'] === null; ?>
                <li>
                    <form method="post" action="<?= e(url('/notifications/' . $n['id'] . '/open')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="notif <?= $unread ? 'is-unread' : '' ?>">
                            <span class="notif-icon tone-<?= e($type->tone()) ?>"><?= icon($type->icon()) ?></span>
                            <span class="notif-main">
                                <span class="notif-title"><?= e($n['title']) ?></span>
                                <?php if ($n['body'] !== ''): ?><span class="notif-body"><?= e($n['body']) ?></span><?php endif; ?>
                                <span class="notif-time" title="<?= e(fmt_datetime($n['created_at'])) ?>"><?= e(time_ago($n['created_at'])) ?></span>
                            </span>
                            <?php if ($unread): ?><span class="notif-dot"><span class="sr-only">Unread</span></span><?php endif; ?>
                        </button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php require BASE_PATH . '/views/partials/pagination.php'; ?>
    <?php endif; ?>
</section>
