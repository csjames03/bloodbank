<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Paginator;
use App\Domain\Actor;

/**
 * Append-only audit trail of who did what.
 */
final class ActivityRepository extends Repository
{
    public function log(string $subject, int $subjectId, string $action, Actor $actor, string $note = ''): void
    {
        $this->db->run(
            'INSERT INTO activity_log (subject, subject_id, action, actor_role, actor_id, actor_name, note)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$subject, $subjectId, $action, $actor->role, $actor->id, $actor->name, mb_substr($note, 0, 255)],
        );
    }

    /**
     * Latest actor name per action for each subject id.
     *
     * @param list<int> $ids
     * @return array<int, array<string, string>> subject_id => [action => actor_name]
     */
    public function actorsFor(string $subject, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = $this->db->all(
            'SELECT DISTINCT ON (subject_id, action) subject_id, action, actor_name
             FROM activity_log
             WHERE subject = ? AND subject_id = ANY (?::int[])
             ORDER BY subject_id, action, id DESC',
            [$subject, '{' . implode(',', array_map('intval', $ids)) . '}'],
        );

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['subject_id']][$row['action']] = $row['actor_name'];
        }

        return $map;
    }

    /** @param array{actor?: ?string, subject?: ?string} $filters */
    public function paginate(array $filters, int $page, int $perPage): Paginator
    {
        $clauses = [];
        $params = [];
        if (!empty($filters['subject'])) {
            $clauses[] = 'subject = :subject';
            $params['subject'] = $filters['subject'];
        }
        if (!empty($filters['actor'])) {
            $clauses[] = 'actor_name ILIKE :actor';
            $params['actor'] = $this->like($filters['actor']);
        }
        $where = $clauses === [] ? '' : 'WHERE ' . implode(' AND ', $clauses);

        $total = (int) $this->db->value("SELECT COUNT(*) FROM activity_log {$where}", $params);
        $items = $this->db->all(
            "SELECT id, subject, subject_id, action, actor_role, actor_name, note, created_at
             FROM activity_log {$where} ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset",
            [...$params, 'limit' => $perPage, 'offset' => Paginator::offset($page, $perPage)],
        );

        return new Paginator($items, $total, $page, $perPage);
    }
}
