<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Repositories\BloodTypeRepository;

final class StockController
{
    public function index(): void
    {
        $inventory = (new BloodTypeRepository())->inventory();

        View::render('stocks/index', [
            'title'      => 'Blood stock',
            'inventory'  => $inventory,
            'totals'     => [
                'available'  => array_sum(array_column($inventory, 'bags')),
                'donated'    => array_sum(array_column($inventory, 'donated')),
                'dispatched' => array_sum(array_column($inventory, 'dispatched')),
                'pending'    => array_sum(array_column($inventory, 'pending')),
            ],
        ]);
    }
}
