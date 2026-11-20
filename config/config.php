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
        // Whole blood (CPDA-1) keeps for 35 days from collection.
        'shelf_life_days'     => (int) Env::get('SHELF_LIFE_DAYS', '35'),
        // Staff are alerted when available units will expire within this many days.
        'expiry_warning_days' => (int) Env::get('EXPIRY_WARNING_DAYS', '7'),
        // Minimum days between two whole-blood donations by the same donor.
        'donation_interval_days' => (int) Env::get('DONATION_INTERVAL_DAYS', '56'),
    ],

    // Days of supply = usable units ÷ average daily use over the window.
    'supply' => [
        'usage_window_days' => (int) Env::get('SUPPLY_WINDOW_DAYS', '30'),
        // Below this many days of supply a group is Critical, below `low_days` Low.
        'critical_days'     => (float) Env::get('SUPPLY_CRITICAL_DAYS', '3'),
        'low_days'          => (float) Env::get('SUPPLY_LOW_DAYS', '7'),
        // Donors contacted within this many days are shown as "recently contacted".
        'recall_cooldown_days' => (int) Env::get('RECALL_COOLDOWN_DAYS', '7'),
    ],

    // Live updates: "poll" (works everywhere, incl. `php -S`) or "sse"
    // (Server-Sent Events via PostgreSQL LISTEN/NOTIFY; needs a server that
    // handles concurrent requests, e.g. Apache or Nginx + PHP-FPM).
    'realtime' => [
        'mode'           => Env::get('REALTIME_MODE', 'poll'),
        'poll_seconds'   => (int) Env::get('POLL_SECONDS', '30'),
        'stream_seconds' => (int) Env::get('STREAM_SECONDS', '25'),
    ],

    'pagination' => [
        'per_page' => 12,
    ],
];
