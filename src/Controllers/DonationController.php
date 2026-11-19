<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Paginator;
use App\Core\Request;
use App\Core\Validator;
use App\Core\View;
use App\Domain\Actor;
use App\Domain\BloodGroup;
use App\Domain\DomainException;
use App\Repositories\BloodTypeRepository;
use App\Repositories\DonationRepository;
use App\Services\DonationService;

final class DonationController
{
    public function index(Request $request): void
    {
        $filters = [
            'q'    => (string) $request->query('q', ''),
            'type' => in_array($request->query('type'), BloodGroup::codes(), true) ? $request->query('type') : null,
        ];

        View::render('donations/index', [
            'title'     => 'Donations',
            'filters'   => $filters,
            'page'      => (new DonationRepository())->paginate(
                $filters,
                Paginator::currentPage($request->query('page')),
                (int) config('pagination.per_page'),
            ),
            'stats'     => (new DonationRepository())->stats(),
            'stock'     => (new BloodTypeRepository())->stockByCode(),
        ]);
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request, 'donation-create');

        try {
            $result = (new DonationService())->record($data, BloodGroup::from($data['blood_type']), $data['bags'], Actor::current());
        } catch (DomainException $e) {
            back_with_errors([$e->field ?? 'bags' => $e->getMessage()], $request->all(), 'donation-create');
        }

        flash('success', sprintf(
            'Donation recorded — %s of %s held in quarantine until screening. %s',
            plural($data['bags'], 'bag'),
            $data['blood_type'],
            $result['returning']
                ? sprintf('Welcome back, %s — that’s donation #%d.', $data['first_name'], $result['donations'])
                : sprintf('Thank you, %s!', $data['first_name']),
        ));
        redirect_back('/donations');
    }

    public function update(Request $request, int $id): void
    {
        $data = $this->validate($request, 'donation-edit');

        try {
            (new DonationService())->update($id, $data, BloodGroup::from($data['blood_type']), $data['bags'], Actor::current());
        } catch (DomainException $e) {
            back_with_errors([$e->field ?? 'bags' => $e->getMessage()], $request->all(), 'donation-edit');
        }

        flash('success', 'Donation updated.');
        redirect_back('/donations');
    }

    public function destroy(Request $request, int $id): void
    {
        try {
            (new DonationService())->delete($id, Actor::current());
            flash('success', 'Donation deleted before screening.');
        } catch (DomainException $e) {
            flash('error', $e->getMessage());
        }

        redirect_back('/donations');
    }

    /** @return array<string, mixed> */
    private function validate(Request $request, string $dialog): array
    {
        $input = $request->all();
        $maxBags = (int) config('inventory.max_donation_bags');

        $v = Validator::make($input, [
            'first_name'     => ['required', 'max:60'],
            'middle_name'    => ['nullable', 'max:60'],
            'last_name'      => ['required', 'max:60'],
            'sex'            => ['required', 'in:Male,Female'],
            'age'            => ['required', 'int', 'between:16,65'],
            'contact'        => ['required', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'address'        => ['required', 'max:255'],
            'blood_type'     => ['required', 'in:' . implode(',', BloodGroup::codes())],
            'bags'           => ['required', 'int', "between:1,{$maxBags}"],
            'medical_status' => ['nullable', 'max:255'],
        ], [
            'contact' => 'contact number',
            'bags'    => 'number of bags',
            'age'     => 'donor age',
        ]);

        if ($v->fails()) {
            back_with_errors($v->errors(), $input, $dialog);
        }

        return $v->validated();
    }
}
