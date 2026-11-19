<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\View;
use App\Domain\NotificationType;
use App\Repositories\NotificationRepository;
use App\Repositories\RequestRepository;

/**
 * The signed-in user's inbox: list, open (mark read + follow link),
 * mark all read, and a small JSON endpoint the layout polls for live updates.
 */
final class NotificationController
{
    private readonly NotificationRepository $inbox;
    private readonly string $role;
    private readonly int $id;

    public function __construct()
    {
        $this->inbox = new NotificationRepository();
        $this->role = (string) Auth::user()['role'];
        $this->id = (int) Auth::id();
    }

    public function index(Request $request): void
    {
        $unreadOnly = $request->query('filter') === 'unread';
        $this->inbox->prune($this->role, $this->id);

        View::render('notifications/index', [
            'title'      => 'Notifications',
            'unreadOnly' => $unreadOnly,
            'counts'     => $this->inbox->counts($this->role, $this->id),
            'page'       => $this->inbox->paginate(
                $this->role,
                $this->id,
                $unreadOnly,
                Paginator::currentPage($request->query('page')),
                (int) config('pagination.per_page'),
            ),
        ]);
    }

    /** Mark one read and go where it points. */
    public function open(Request $request, int $id): void
    {
        $notification = $this->inbox->markRead($id, $this->role, $this->id);

        if ($notification === null) {
            flash('error', 'Notification not found.');
            redirect('/notifications');
        }

        redirect($notification['link'] ?: '/notifications');
    }

    public function readAll(): void
    {
        $count = $this->inbox->markAllRead($this->role, $this->id);
        flash('success', $count > 0 ? 'Marked ' . plural($count, 'notification') . ' as read.' : 'You’re all caught up.');
        redirect_back('/notifications');
    }

    /**
     * GET /notifications/poll?after=<id>
     * Returns the unread count and any unread notifications newer than `after`.
     */
    public function poll(Request $request): void
    {
        // Don't hold the session lock while the query runs.
        session_write_close();

        $after = max(0, (int) $request->query('after', 0));
        $counts = $this->inbox->counts($this->role, $this->id);

        $items = array_map(static function (array $n): array {
            $type = NotificationType::present($n['type']);

            return [
                'id'    => (int) $n['id'],
                'title' => $n['title'],
                'body'  => $n['body'],
                'tone'  => $type->tone(),
                'icon'  => $type->icon(),
                'time'  => time_ago($n['created_at']),
            ];
        }, $after > 0 ? $this->inbox->unreadSince($this->role, $this->id, $after) : []);

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode([
            'unread'  => $counts['unread'],
            'latest'  => $counts['latest'],
            'items'   => $items,
            // Staff nav also shows the pending-request count; keep it in step.
            'pending' => Auth::isAdmin() ? (new RequestRepository())->summary()['pending']['count'] : null,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
