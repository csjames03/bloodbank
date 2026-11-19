<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Env;
use App\Core\HttpException;
use App\Core\Session;
use App\Core\View;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/src/autoload.php';

Env::load(BASE_PATH . '/.env');
Config::set(require BASE_PATH . '/config/config.php');

date_default_timezone_set((string) config('app.timezone'));

$debug = (bool) config('app.debug');
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');

// Base path: configured, or derived from where index.php lives (e.g. "/bloodbank/public").
$base = (string) config('app.base_path');
if ($base === '' && PHP_SAPI !== 'cli') {
    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
}
define('APP_BASE', rtrim($base, '/'));

// Turn warnings/notices into exceptions so bugs never fail silently.
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (Throwable $e) use ($debug): void {
    $status = $e instanceof HttpException ? $e->status : 500;

    if ($status === 500) {
        error_log((string) $e);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $message = match (true) {
        $e instanceof HttpException => $e->getMessage(),
        $e instanceof PDOException && str_contains($e->getMessage(), 'SQLSTATE[08006]')
            => 'Cannot connect to the database. Check your DB settings and run `php database/setup.php`.',
        default => 'Something went wrong on our side. Please try again.',
    };

    try {
        View::render('errors/error', [
            'title'   => $status === 404 ? 'Page not found' : 'Error',
            'status'  => $status,
            'message' => $message,
            'detail'  => $debug && $status === 500 ? (string) $e : null,
        ], 'auth', $status);
    } catch (Throwable) {
        http_response_code($status);
        echo '<h1>' . $status . '</h1><p>' . e($message) . '</p>';
    }
});

Session::start();
