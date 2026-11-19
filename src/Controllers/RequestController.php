<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Validator;
use App\Core\View;
use App\Domain\Actor;
use App\Domain\BloodGroup;
use App\Domain\DomainException;
use App\Domain\Priority;
use App\Domain\RequestStatus;
use App\Repositories\AccountRepository;
use App\Repositories\BloodTypeRepository;
use App\Repositories\RequestRepository;
use App\Services\RequestService;

final class RequestController
{
    /** Admins see every request; hospitals only their own. */
    public function index(Request $request): void
    {
        $statuses = array_map(static fn (RequestStatus $s) => $s->value, RequestStatus::cases());
        $filters = [
            'q'           => (string) $request->query('q', ''),
            'type'        => in_array($request->query('type'), BloodGroup::codes(), true) ? $request->query('type') : null,
            'status'      => in_array($request->query('status'), $statuses, true) ? $request->query('status') : null,
            'hospital_id' => Auth::isHospital() ? Auth::id() : null,
        ];

        $requests = new RequestRepository();

        View::render('requests/index', [
            'title'     => Auth::isAdmin() ? 'Requests' : 'My requests',
            'filters'   => $filters,
            'page'      => $requests->paginate(
                $filters,
                Paginator::currentPage($request->query('page')),
                (int) config('pagination.per_page'),
            ),
            'summary'   => $requests->summary($filters['hospital_id']),
            'stock'     => (new BloodTypeRepository())->stockByCode(),
            'expiry'    => Auth::isAdmin() ? (new BloodTypeRepository())->earliestExpiry() : [],
            'hospitals' => Auth::isAdmin() ? (new AccountRepository())->hospitals() : [],
        ]);
    }

    public function store(Request $request): void
    {
        $input = $request->all();
        $maxBags = (int) config('inventory.max_request_bags');

        $rules = [
            'doctor_name' => ['required', 'min:2', 'max:100'],
            'blood_type'  => ['required', 'in:' . implode(',', BloodGroup::codes())],
            'bags'        => ['required', 'int', "between:1,{$maxBags}"],
            'priority'    => ['required', 'in:' . implode(',', Priority::values())],
            'needed_by'   => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/'],
        ];
        if (Auth::isAdmin()) {
            $rules['hospital_id'] = ['required', 'int'];
        }

        $input['priority'] = ($input['priority'] ?? '') === '' ? Priority::Routine->value : $input['priority'];

        $v = Validator::make($input, $rules, [
            'doctor_name' => "doctor's name",
            'bags'        => 'number of bags',
            'hospital_id' => 'hospital',
            'needed_by'   => '“needed by” time',
        ]);
        if ($v->fails()) {
            back_with_errors($v->errors(), $input, 'request-create');
        }
        $data = $v->validated();

        $neededBy = null;
        if (!empty($data['needed_by'])) {
            $neededBy = new \DateTimeImmutable($data['needed_by']);
            if ($neededBy < new \DateTimeImmutable('-5 minutes')) {
                back_with_errors(['needed_by' => 'The “needed by” time is in the past.'], $input, 'request-create');
            }
        }

        $hospitalId = Auth::isAdmin() ? (int) $data['hospital_id'] : (int) Auth::id();
        if ((new AccountRepository())->findHospital($hospitalId) === null) {
            back_with_errors(['hospital_id' => 'Please choose a valid hospital.'], $input, 'request-create');
        }

        $group = BloodGroup::from($data['blood_type']);
        $priority = Priority::from($data['priority']);

        try {
            (new RequestService())->create($hospitalId, $group, $data['doctor_name'], $data['bags'], $priority, $neededBy, Actor::current());
        } catch (DomainException $e) {
            back_with_errors([$e->field ?? 'bags' => $e->getMessage()], $input, 'request-create');
        }

        $message = sprintf(
            '%sRequest for %s of %s submitted.',
            $priority === Priority::Routine ? '' : $priority->label() . ' — ',
            plural($data['bags'], 'bag'),
            $group->value,
        );
        $available = (new BloodTypeRepository())->stockByCode()[$group->value] ?? 0;
        if ($data['bags'] > $available) {
            $message .= sprintf(
                ' Only %s of %s are in stock right now, so the blood bank may send a compatible group or a partial amount.',
                plural($available, 'bag'),
                $group->value,
            );
        }

        flash('success', $message);
        redirect_back('/requests');
    }

    /** Staff choose what to send: `alloc[<code>]=<bags>`; empty means "use the suggestion". */
    public function approve(Request $request, int $id): void
    {
        $allocation = [];
        $raw = $request->input('alloc', []);
        if (is_array($raw)) {
            foreach ($raw as $code => $bags) {
                if (!is_string($code) || !is_scalar($bags)) {
                    continue;
                }
                $n = filter_var(trim((string) $bags), FILTER_VALIDATE_INT);
                if ($n === false || $n < 0) {
                    flash('error', "Enter a whole number of bags for {$code}.");
                    redirect_back('/requests');
                }
                $allocation[$code] = $n;
            }
        }

        try {
            $result = (new RequestService())->approve($id, $allocation, Actor::current());
            flash('success', sprintf(
                'Dispatched %s — stock deducted and the hospital notified.',
                RequestService::describe($result['allocation']),
            ));
        } catch (DomainException $e) {
            flash('error', $e->getMessage());
        }

        redirect_back('/requests');
    }

    public function receive(Request $request, int $id): void
    {
        $this->transition(
            fn (RequestService $s) => $s->receive($id, Actor::current()),
            Auth::isAdmin() ? 'Recorded as received on the hospital’s behalf.' : 'Thanks — delivery confirmed as received.',
        );
    }

    public function cancel(Request $request, int $id): void
    {
        $this->transition(fn (RequestService $s) => $s->cancel($id, Actor::current()), 'Request cancelled.');
    }

    private function transition(callable $action, string $success): void
    {
        try {
            $action(new RequestService());
            flash('success', $success);
        } catch (DomainException $e) {
            flash('error', $e->getMessage());
        }

        redirect_back('/requests');
    }
}
