<?php

declare(strict_types=1);

use App\Controllers\ActivityController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\DonationController;
use App\Controllers\NotificationController;
use App\Controllers\RequestController;
use App\Controllers\ScreeningController;
use App\Controllers\UnitController;
use App\Controllers\StockController;
use App\Core\Router;

$router = new Router();

// Authentication
$router->get('/login', [AuthController::class, 'hospitalForm'], 'guest');
$router->post('/login', [AuthController::class, 'hospitalLogin'], 'guest');
$router->get('/admin/login', [AuthController::class, 'adminForm'], 'guest');
$router->post('/admin/login', [AuthController::class, 'adminLogin'], 'guest');
$router->post('/logout', [AuthController::class, 'logout'], 'auth');

// Dashboard (role-aware)
$router->get('/', [DashboardController::class, 'index'], 'auth');

// Blood requests — hospitals see and cancel their own; admins manage all.
$router->get('/requests', [RequestController::class, 'index'], 'auth');
$router->post('/requests', [RequestController::class, 'store'], 'auth');
$router->post('/requests/{id}/approve', [RequestController::class, 'approve'], 'admin');
// Hospitals confirm their own deliveries; staff may record it for them.
$router->post('/requests/{id}/receive', [RequestController::class, 'receive'], 'auth');
$router->post('/requests/{id}/cancel', [RequestController::class, 'cancel'], 'auth');

// Donations — hospitals can log walk-in donors; admins manage records.
$router->get('/donations', [DonationController::class, 'index'], 'admin');
$router->post('/donations', [DonationController::class, 'store'], 'auth');
$router->post('/donations/{id}', [DonationController::class, 'update'], 'admin');
$router->post('/donations/{id}/delete', [DonationController::class, 'destroy'], 'admin');

// Inventory
$router->get('/stocks', [StockController::class, 'index'], 'admin');

// Lab screening and unit-level inventory
$router->get('/screening', [ScreeningController::class, 'index'], 'admin');
$router->post('/screening/{id}/pass', [ScreeningController::class, 'pass'], 'admin');
$router->post('/screening/{id}/fail', [ScreeningController::class, 'fail'], 'admin');
$router->get('/units', [UnitController::class, 'index'], 'admin');
$router->post('/units/{id}/discard', [UnitController::class, 'discard'], 'admin');

// Audit trail
$router->get('/activity', [ActivityController::class, 'index'], 'admin');

// Notifications — every user has their own inbox.
$router->get('/notifications', [NotificationController::class, 'index'], 'auth');
$router->get('/notifications/poll', [NotificationController::class, 'poll'], 'auth');
$router->post('/notifications/read-all', [NotificationController::class, 'readAll'], 'auth');
$router->post('/notifications/{id}/open', [NotificationController::class, 'open'], 'auth');

return $router;
