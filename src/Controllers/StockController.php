<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Repositories\BloodTypeRepository;
use App\Services\SupplyService;

final class StockController
{
    public function index(): void
    {
        $inventory = (new BloodTypeRepository())->inventory();
        $sum = static fn (string $key) => array_sum(array_map('intval', array_column($inventory, $key)));

        View::render('stocks/index', [
            'title'      => 'Blood stock',
            'inventory'  => $inventory,
            'supply'     => (new SupplyService())->byCode(),
            'totals'     => [
                'available'  => $sum('bags'),
                'expiring'   => $sum('expiring'),
                'quarantine' => $sum('quarantine'),
                'wasted'     => $sum('wasted'),
                'donated'    => $sum('donated'),
                'dispatched' => $sum('dispatched'),
                'pending'    => $sum('pending'),
            ],
        ]);
    }
}
