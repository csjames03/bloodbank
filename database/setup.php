<?php

declare(strict_types=1);

/*
 * Creates the database (if needed), loads the schema and the demo data.
 *
 *   php database/setup.php            create DB if missing, (re)build schema + demo data
 *   php database/setup.php --no-seed  schema + accounts only, no demo donors/requests
 *
 * WARNING: rebuilding the schema drops every existing table.
 */

if (PHP_SAPI !== 'cli') {
    exit("Run this script from the command line.\n");
}

require dirname(__DIR__) . '/src/autoload.php';

use App\Core\Env;

Env::load(dirname(__DIR__) . '/.env');
$config = require dirname(__DIR__) . '/config/config.php';
$db = $config['db'];
$seed = !in_array('--no-seed', $argv, true);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

function line(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

try {
    // 1. Create the database from the maintenance DB.
    $server = new PDO(
        sprintf('pgsql:host=%s;port=%d;dbname=postgres', $db['host'], $db['port']),
        $db['username'],
        $db['password'],
        $options,
    );

    $exists = $server->prepare('SELECT 1 FROM pg_database WHERE datname = ?');
    $exists->execute([$db['database']]);

    if ($exists->fetchColumn()) {
        line("• Database \"{$db['database']}\" already exists");
    } else {
        $server->exec('CREATE DATABASE ' . '"' . str_replace('"', '""', $db['database']) . '"' . " ENCODING 'UTF8'");
        line("✓ Created database \"{$db['database']}\"");
    }
    $server = null;

    // 2. Schema.
    $pdo = new PDO(
        sprintf('pgsql:host=%s;port=%d;dbname=%s', $db['host'], $db['port'], $db['database']),
        $db['username'],
        $db['password'],
        $options,
    );

    $pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));
    line('✓ Schema created');

    // 3. Accounts (hashed in PHP).
    $admin = $pdo->prepare('INSERT INTO admins (username, full_name, password_hash) VALUES (?, ?, ?)');
    $admin->execute(['admin', 'Administrator', password_hash('admin123', PASSWORD_DEFAULT)]);

    $hospital = $pdo->prepare(
        'INSERT INTO hospitals (name, username, password_hash, address, contact) VALUES (?, ?, ?, ?, ?)'
    );
    $hospitals = [
        ['Riverside General Hospital',      'riverside',  'Riverside Ave., Angeles City, Pampanga', '(045) 555-0101'],
        ['Northpoint Medical Center',       'northpoint', 'MacArthur Hwy., San Fernando, Pampanga', '(045) 555-0102'],
        ['Sta. Clara Community Hospital',   'staclara',   'Romulo Blvd., Tarlac City, Tarlac',      '(045) 555-0103'],
    ];
    foreach ($hospitals as [$name, $username, $address, $contact]) {
        $hospital->execute([$name, $username, password_hash('hospital123', PASSWORD_DEFAULT), $address, $contact]);
    }
    line('✓ Admin and hospital accounts created');

    // 4. Demo data.
    if ($seed) {
        $pdo->exec(file_get_contents(__DIR__ . '/seed.sql'));
        line('✓ Demo donors, donations and requests loaded');
    }

    line('');
    line('Done. Sign in with:');
    line('  Admin     → /admin/login   admin / admin123');
    line('  Hospital  → /login         riverside | northpoint | staclara / hospital123');
} catch (PDOException $e) {
    fwrite(STDERR, '✗ Database setup failed: ' . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, '  Check DB_* settings in .env (defaults: postgres / postgres @ 127.0.0.1:5432).' . PHP_EOL);
    exit(1);
}
