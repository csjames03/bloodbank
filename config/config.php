<?php

declare(strict_types=1);

/*
 * Application configuration.
 *
 * Every value can be overridden with an environment variable or a `.env`
 * file in the project root (see `.env.example`). The defaults target a local
 * PostgreSQL install using the stock `postgres` / `postgres` credentials.
 */

use App\Core\Env;

return [
    'app' => [
        'name'     => Env::get('APP_NAME', 'LifeLine Blood Bank'),
        'debug'    => Env::bool('APP_DEBUG', true),
        'timezone' => Env::get('APP_TIMEZONE', 'Asia/Manila'),
        // Leave empty to auto-detect from the script location.
        'base_path' => Env::get('APP_BASE_PATH', ''),
    ],

    'db' => [
        'host'     => Env::get('DB_HOST', '127.0.0.1'),
        'port'     => (int) Env::get('DB_PORT', '5432'),
        'database' => Env::get('DB_DATABASE', 'bloodbank'),
        'username' => Env::get('DB_USERNAME', 'postgres'),
        'password' => Env::get('DB_PASSWORD', 'postgres'),
    ],

    'inventory' => [
        // A blood type at or below this many bags is flagged as "low".
        'low_stock_threshold' => (int) Env::get('LOW_STOCK_THRESHOLD', '5'),
        'max_request_bags'    => 20,
        'max_donation_bags'   => 2,
        // Minimum days between two whole-blood donations by the same donor.
        'donation_interval_days' => (int) Env::get('DONATION_INTERVAL_DAYS', '56'),
    ],

    'pagination' => [
        'per_page' => 12,
    ],
];
