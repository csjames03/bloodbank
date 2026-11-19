<?php

declare(strict_types=1);

/*
 * Front controller — every request that isn't a static file lands here.
 *
 *   php -S localhost:8000 -t public public/index.php
 */

// Let PHP's built-in server serve real files (CSS, JS, images) directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($file !== __DIR__ . '/' && is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Request;

/** @var App\Core\Router $router */
$router = require BASE_PATH . '/src/routes.php';
$router->dispatch(Request::capture(APP_BASE));
