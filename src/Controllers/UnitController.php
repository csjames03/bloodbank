<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Paginator;
use App\Core\Request;
use App\Core\View;
use App\Domain\Actor;
use App\Domain\BloodGroup;
use App\Domain\DiscardReason;
use App\Domain\DomainException;
use App\Domain\UnitStatus;
use App\Repositories\UnitRepository;
use App\Services\InventoryService;

/** Every physical bag: where it is, when it expires, and why it left. */
final class UnitController
{
    public function index(Request $request): void
    {
        $statuses = [...array_map(static fn (UnitStatus $s) => $s->value, UnitStatus::cases()), 'expiring'];
        $filters = [
            'q'      => (string) $request->query('q', ''),
            'status' => in_array($request->query('status'), $statuses, true) ? $request->query('status') : null,
            'type'   => in_array($request->query('type'), BloodGroup::codes(), true) ? $request->query('type') : null,
        ];

        $units = new UnitRepository();

        View::render('units/index', [
            'title'   => 'Units',
            'filters' => $filters,
            'counts'  => $units->statusCounts(),
            'page'    => $units->paginate($filters, Paginator::currentPage($request->query('page')), 20),
        ]);
    }

    public function discard(Request $request, int $id): void
    {
        $reason = DiscardReason::tryFrom((string) $request->input('reason'));
        if ($reason === null) {
            flash('error', 'Choose why the unit is being discarded.');
            redirect_back('/units');
        }

        try {
            (new InventoryService())->discard($id, $reason, mb_substr((string) $request->input('note', ''), 0, 100), Actor::current());
            flash('success', 'Unit discarded — ' . strtolower($reason->label()) . '.');
        } catch (DomainException $e) {
            flash('error', $e->getMessage());
        }

        redirect_back('/units');
    }
}
