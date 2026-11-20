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

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($this->snapshot(max(0, (int) $request->query('after', 0))), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * GET /notifications/stream — Server-Sent Events.
     *
     * Sends a snapshot straight away, then waits on PostgreSQL NOTIFY and
     * pushes a fresh snapshot the moment something for this user commits.
     * The stream closes after `realtime.stream_seconds` (the browser's
     * EventSource reconnects automatically, resuming from Last-Event-ID),
     * which keeps PHP workers from being held forever. `?max=0` returns just
     * the first snapshot (used by tests and health checks).
     */
    public function stream(Request $request): void
    {
        session_write_close();
        ignore_user_abort(false);
        set_time_limit(0);

        $limit = (int) config('realtime.stream_seconds', 25);
        $max = $request->query('max');
        $seconds = $max === null ? $limit : max(0, min($limit, (int) $max));
        $lastId = max(0, (int) ($_SERVER['HTTP_LAST_EVENT_ID'] ?? $request->query('after', 0)));

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Accel-Buffering: no'); // tell Nginx not to buffer
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        $send = function (array $data): void {
            echo 'id: ' . $data['latest'] . "\n";
            echo "event: inbox\n";
            echo 'data: ' . json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) . "\n\n";
            flush();
        };

        $snapshot = $this->snapshot($lastId);
        $send($snapshot);
        $lastId = max($lastId, $snapshot['latest']);

        $db = \App\Core\Database::instance();
        $db->listen(NotificationRepository::CHANNEL);
        $me = "{$this->role}:{$this->id}";
        $mine = [$me, "{$this->role}:*"];
        $deadline = microtime(true) + $seconds;
        $lastBeat = microtime(true);

        while (($left = $deadline - microtime(true)) > 0 && !connection_aborted()) {
            $notice = $db->waitForNotify((int) min(5000, max(50, $left * 1000)));

            if ($notice !== null && in_array($notice['payload'] ?? '', $mine, true)) {
                $snapshot = $this->snapshot($lastId);
                $send($snapshot);
                $lastId = max($lastId, $snapshot['latest']);
                $lastBeat = microtime(true);
            } elseif (microtime(true) - $lastBeat >= 15) {
                echo ": keep-alive\n\n"; // comment line keeps proxies from timing out
                flush();
                $lastBeat = microtime(true);
            }
        }

        echo "retry: 1000\n\n";
        flush();
    }

    /** @return array{unread: int, latest: int, items: list<array<string, mixed>>, pending: ?int} */
    private function snapshot(int $after): array
    {
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

        return [
            'unread'  => $counts['unread'],
            'latest'  => $counts['latest'],
            'items'   => $items,
            // Staff nav also shows the pending-request count; keep it in step.
            'pending' => Auth::isAdmin() ? (new RequestRepository())->summary()['pending']['count'] : null,
        ];
    }

    /**
     * Live-update mode actually in effect. SSE holds a connection open, so it
     * is refused on PHP's single-threaded built-in server (it would block every
     * other request) unless PHP_CLI_SERVER_WORKERS gives it more workers.
     */
    public static function realtimeMode(): string
    {
        $mode = config('realtime.mode', 'poll') === 'sse' ? 'sse' : 'poll';
        if ($mode === 'sse' && PHP_SAPI === 'cli-server' && (int) getenv('PHP_CLI_SERVER_WORKERS') < 2) {
            return 'poll';
        }

        return $mode;
    }
}
