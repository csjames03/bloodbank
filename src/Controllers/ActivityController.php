<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Paginator;
use App\Core\Request;
use App\Core\View;
use App\Repositories\ActivityRepository;

/** Staff-only audit trail: who did what, and when. */
final class ActivityController
{
    public function index(Request $request): void
    {
        $filters = [
            'subject' => in_array($request->query('subject'), ['request', 'donation'], true) ? $request->query('subject') : null,
            'actor'   => (string) $request->query('actor', ''),
        ];

        View::render('activity/index', [
            'title'   => 'Activity',
            'filters' => $filters,
            'page'    => (new ActivityRepository())->paginate(
                $filters,
                Paginator::currentPage($request->query('page')),
                25,
            ),
        ]);
    }
}
