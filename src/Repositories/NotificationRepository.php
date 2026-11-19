<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Paginator;
use App\Domain\NotificationType;

/**
 * Inbox storage. A recipient is identified by role (`admin` | `hospital`) and id.
 */
final class NotificationRepository extends Repository
{
    private const COLUMNS = 'id, type, title, body, link, request_id, created_at, read_at';

    /** Send to every staff account. */
    public function toAdmins(NotificationType $type, string $title, string $body = '', ?string $link = null, ?int $requestId = null): void
    {
        $this->db->run(
            "INSERT INTO notifications (recipient_role, recipient_id, type, title, body, link, request_id)
             SELECT 'admin', id, ?, ?, ?, ?, ? FROM admins",
            [$type->value, $title, $body, $link, $requestId],
        );
    }

    public function toHospital(int $hospitalId, NotificationType $type, string $title, string $body = '', ?string $link = null, ?int $requestId = null): void
    {
        $this->db->run(
            "INSERT INTO notifications (recipient_role, recipient_id, type, title, body, link, request_id)
             VALUES ('hospital', ?, ?, ?, ?, ?, ?)",
            [$hospitalId, $type->value, $title, $body, $link, $requestId],
        );
    }

    public function paginate(string $role, int $id, bool $unreadOnly, int $page, int $perPage): Paginator
    {
        $where = 'WHERE recipient_role = :role AND recipient_id = :id' . ($unreadOnly ? ' AND read_at IS NULL' : '');
        $params = ['role' => $role, 'id' => $id];

        $total = (int) $this->db->value("SELECT COUNT(*) FROM notifications {$where}", $params);
        $items = $this->db->all(
            'SELECT ' . self::COLUMNS . " FROM notifications {$where} ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset",
            [...$params, 'limit' => $perPage, 'offset' => Paginator::offset($page, $perPage)],
        );

        return new Paginator($items, $total, $page, $perPage);
    }

    /**
     * Unread notifications newer than `$afterId`, oldest first — used by live polling.
     *
     * @return list<array<string, mixed>>
     */
    public function unreadSince(string $role, int $id, int $afterId, int $limit = 5): array
    {
        return array_reverse($this->db->all(
            'SELECT ' . self::COLUMNS . ' FROM notifications
             WHERE recipient_role = ? AND recipient_id = ? AND id > ? AND read_at IS NULL
             ORDER BY id DESC LIMIT ?',
            [$role, $id, $afterId, $limit],
        ));
    }

    /** @return array{unread: int, total: int, latest: int} */
    public function counts(string $role, int $id): array
    {
        $row = $this->db->one(
            'SELECT COUNT(*) FILTER (WHERE read_at IS NULL) AS unread, COUNT(*) AS total, COALESCE(MAX(id), 0) AS latest
             FROM notifications WHERE recipient_role = ? AND recipient_id = ?',
            [$role, $id],
        );

        return ['unread' => (int) $row['unread'], 'total' => (int) $row['total'], 'latest' => (int) $row['latest']];
    }

    /**
     * Mark one notification read. Returns it, or null when it doesn't belong to the recipient.
     *
     * @return array<string, mixed>|null
     */
    public function markRead(int $notificationId, string $role, int $id): ?array
    {
        return $this->db->one(
            'UPDATE notifications SET read_at = COALESCE(read_at, now())
             WHERE id = ? AND recipient_role = ? AND recipient_id = ?
             RETURNING ' . self::COLUMNS,
            [$notificationId, $role, $id],
        );
    }

    public function markAllRead(string $role, int $id): int
    {
        return $this->db->run(
            'UPDATE notifications SET read_at = now() WHERE recipient_role = ? AND recipient_id = ? AND read_at IS NULL',
            [$role, $id],
        )->rowCount();
    }

    /** Drop read notifications older than `$days` so inboxes don't grow forever. */
    public function prune(string $role, int $id, int $days = 90): void
    {
        $this->db->run(
            "DELETE FROM notifications
             WHERE recipient_role = ? AND recipient_id = ? AND read_at IS NOT NULL
               AND created_at < now() - make_interval(days => ?)",
            [$role, $id, $days],
        );
    }
}
