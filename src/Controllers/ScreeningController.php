<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Domain\Actor;
use App\Domain\BloodGroup;
use App\Domain\DomainException;
use App\Domain\ScreeningFailure;
use App\Repositories\UnitRepository;
use App\Services\ScreeningService;

/** Lab screening queue: release quarantined donations to stock, or fail them. */
final class ScreeningController
{
    public function index(): void
    {
        View::render('screening/index', [
            'title' => 'Screening',
            'queue' => (new UnitRepository())->screeningQueue(),
        ]);
    }

    public function pass(Request $request, int $id): void
    {
        $group = BloodGroup::tryFrom((string) $request->input('confirmed_group'));
        if ($group === null) {
            flash('error', 'Choose the blood group the lab confirmed.');
            redirect_back('/screening');
        }

        try {
            $result = (new ScreeningService())->pass($id, $group, Actor::current());
            flash('success', sprintf(
                'Screening passed — %s of %s released to stock.%s%s',
                plural($result['released'], 'unit'),
                $group->value,
                $result['regrouped'] ? " Re-grouped {$result['regrouped']}." : '',
                $result['expired'] ? " {$result['expired']} expired while waiting and were not released." : '',
            ));
        } catch (DomainException $e) {
            flash('error', $e->getMessage());
        }

        redirect_back('/screening');
    }

    public function fail(Request $request, int $id): void
    {
        $failure = ScreeningFailure::tryFrom((string) $request->input('reason'));
        if ($failure === null) {
            flash('error', 'Choose why the donation failed screening.');
            redirect_back('/screening');
        }

        try {
            $count = (new ScreeningService())->fail($id, $failure, Actor::current());
            flash('success', sprintf(
                'Recorded as failed (%s). %s discarded%s.',
                $failure->label(),
                plural($count, 'unit'),
                $failure->isPermanentDeferral() ? ' and the donor permanently deferred' : ($failure->deferralDays() ? ' and the donor deferred' : ''),
            ));
        } catch (DomainException $e) {
            flash('error', $e->getMessage());
        }

        redirect_back('/screening');
    }
}
