<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Paginator;
use App\Core\Request;
use App\Core\View;
use App\Domain\Actor;
use App\Domain\BloodGroup;
use App\Domain\DomainException;
use App\Domain\RecallOutcome;
use App\Repositories\DonorRepository;
use App\Services\RecallService;
use App\Services\SupplyService;

/**
 * Donor recall: when a group runs short, call in donors who can give again.
 */
final class RecallController
{
    public function index(Request $request): void
    {
        $forecast = (new SupplyService())->forecast();
        $group = $this->group($request->query('group'), $forecast);

        View::render('recall/index', [
            'title'    => 'Donor recall',
            'group'    => $group,
            'forecast' => $forecast,
            'row'      => array_column($forecast, null, 'code')[$group],
            'donors'   => (new DonorRepository())->eligible($group, Paginator::currentPage($request->query('page')), 15),
            'summary'  => (new DonorRepository())->contactSummary($group, (int) config('supply.recall_cooldown_days', 7)),
        ]);
    }

    public function contact(Request $request, int $id): void
    {
        $outcome = RecallOutcome::tryFrom((string) $request->input('outcome'));
        if ($outcome === null) {
            flash('error', 'Choose how the call went.');
            redirect_back('/recall');
        }

        try {
            $donor = (new RecallService())->logContact($id, $outcome, mb_substr((string) $request->input('note', ''), 0, 160), Actor::current());
            flash('success', "Call logged for {$donor['name']}: " . strtolower($outcome->label()) . '.');
        } catch (DomainException $e) {
            flash('error', $e->getMessage());
        }

        redirect_back('/recall');
    }

    /** CSV of every eligible donor of a group, for call sheets or SMS tools. */
    public function export(Request $request): void
    {
        $group = BloodGroup::tryFrom((string) $request->query('group'))?->value ?? 'O-';
        $rows = (new DonorRepository())->eligibleForExport($group);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="recall-' . str_replace(['+', '-'], ['pos', 'neg'], $group) . '-' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so spreadsheets read names correctly
        fputcsv($out, ['Name', 'Blood group', 'Contact', 'Address', 'Donations', 'Last donated', 'Last call', 'Last outcome'], escape: '');
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['name'],
                $r['blood_type'],
                $r['contact'],
                $r['address'],
                $r['donations'],
                $r['last_donated_at'] ? fmt_date($r['last_donated_at'], 'Y-m-d') : '',
                $r['last_contacted_at'] ? fmt_date($r['last_contacted_at'], 'Y-m-d H:i') : '',
                $r['last_outcome'] ? RecallOutcome::from($r['last_outcome'])->label() : '',
            ], escape: '');
        }
        fclose($out);
    }

    /**
     * The requested group, or the group in the worst supply position.
     *
     * @param list<array<string, mixed>> $forecast
     */
    private function group(mixed $requested, array $forecast): string
    {
        if (is_string($requested) && BloodGroup::tryFrom($requested) !== null) {
            return $requested;
        }

        usort($forecast, static fn ($a, $b) => [$b['level']->severity(), $a['days'] ?? INF] <=> [$a['level']->severity(), $b['days'] ?? INF]);

        return $forecast[0]['code'];
    }
}
