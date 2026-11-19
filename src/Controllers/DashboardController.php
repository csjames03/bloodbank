<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Repositories\AccountRepository;
use App\Repositories\BloodTypeRepository;
use App\Repositories\DonationRepository;
use App\Repositories\RequestRepository;
use App\Repositories\UnitRepository;

final class DashboardController
{
    public function index(): void
    {
        Auth::isAdmin() ? $this->admin() : $this->hospital();
    }

    private function admin(): void
    {
        $inventory = (new BloodTypeRepository())->inventory();

        View::render('dashboard/admin', [
            'title'      => 'Dashboard',
            'inventory'  => $inventory,
            'totalStock' => array_sum(array_column($inventory, 'bags')),
            'summary'    => (new RequestRepository())->summary(),
            'donations'  => (new DonationRepository())->stats(),
            'recentDonations' => (new DonationRepository())->recent(6),
            'openRequests'    => (new RequestRepository())->recent(5),
            'stock'      => array_column($inventory, 'bags', 'code'),
            'expiry'     => (new BloodTypeRepository())->earliestExpiry(),
            'hospitals'  => (new AccountRepository())->hospitals(),
            'screening'  => (new UnitRepository())->quarantinedDonations(),
            'expiring'   => (new UnitRepository())->expiringSoon((int) config('inventory.expiry_warning_days'), 6),
        ]);
    }

    private function hospital(): void
    {
        $hospitalId = (int) Auth::id();
        $stock = (new BloodTypeRepository())->all();

        View::render('dashboard/hospital', [
            'title'    => 'Dashboard',
            'hospital' => (new AccountRepository())->findHospital($hospitalId),
            'stock'    => $stock,
            'stockByCode' => array_column($stock, 'bags', 'code'),
            'summary'  => (new RequestRepository())->summary($hospitalId),
            'requests' => (new RequestRepository())->recent(5, $hospitalId),
        ]);
    }
}
