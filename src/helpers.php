<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Session;

/* ---------------------------------------------------------------------------
 | Output
 * ------------------------------------------------------------------------- */

/** HTML-escape a value for safe output. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

/* ---------------------------------------------------------------------------
 | URLs & redirects
 * ------------------------------------------------------------------------- */

function base_path(): string
{
    return defined('APP_BASE') ? APP_BASE : '';
}

/** @param array<string, mixed> $query */
function url(string $path = '/', array $query = []): string
{
    $url = base_path() . '/' . ltrim($path, '/');
    $query = array_filter($query, static fn ($v) => $v !== null && $v !== '');

    return $query === [] ? $url : $url . '?' . http_build_query($query);
}

function asset(string $path): string
{
    $file = BASE_PATH . '/public/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : '1';

    return url($path) . '?v=' . $version;
}

/**
 * Current URL with some query parameters replaced — used by filters and pagination.
 *
 * @param array<string, mixed> $changes
 */
function url_with(array $changes): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    $query = array_filter(
        [...$_GET, ...$changes],
        static fn ($v) => $v !== null && $v !== '',
    );

    return $query === [] ? $path : $path . '?' . http_build_query($query);
}

function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)), true, 303);
    exit;
}

/** Redirect to the previous page (same-origin referer only), or `$fallback`. */
function redirect_back(string $fallback = '/'): never
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';

    if ($referer !== '' && parse_url($referer, PHP_URL_HOST) . (parse_url($referer, PHP_URL_PORT) ? ':' . parse_url($referer, PHP_URL_PORT) : '') === $host) {
        $target = (parse_url($referer, PHP_URL_PATH) ?: '/');
        $query = parse_url($referer, PHP_URL_QUERY);
        header('Location: ' . $target . ($query ? '?' . $query : ''), true, 303);
        exit;
    }

    redirect($fallback);
}

/**
 * Send the user back to the form with validation errors and their input.
 * `$dialog` re-opens the modal the form lives in.
 *
 * @param array<string, string> $errors
 * @param array<string, mixed>  $input
 */
function back_with_errors(array $errors, array $input, ?string $dialog = null, string $fallback = '/'): never
{
    unset($input['_token']);
    Session::flash('errors', $errors);
    Session::flash('old', $input);
    Session::flash('open_dialog', $dialog);
    Session::flash('toast', ['type' => 'error', 'message' => reset($errors) ?: 'Please check the form.']);
    redirect_back($fallback);
}

/* ---------------------------------------------------------------------------
 | Flash, old input & errors
 * ------------------------------------------------------------------------- */

function flash(string $type, string $message): void
{
    Session::flash('toast', ['type' => $type, 'message' => $message]);
}

function old(string $key, mixed $default = ''): string
{
    $old = Session::getFlash('old', []);

    return (string) ($old[$key] ?? $default ?? '');
}

function error(string $key): ?string
{
    return Session::getFlash('errors', [])[$key] ?? null;
}

function has_errors(): bool
{
    return Session::getFlash('errors', []) !== [];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

/* ---------------------------------------------------------------------------
 | Auth shortcuts
 * ------------------------------------------------------------------------- */

function auth_user(): ?array
{
    return Auth::user();
}

function is_admin(): bool
{
    return Auth::isAdmin();
}

/* ---------------------------------------------------------------------------
 | Formatting
 * ------------------------------------------------------------------------- */

function fmt_date(?string $value, string $format = 'M j, Y'): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    return (new DateTimeImmutable($value))->format($format);
}

function fmt_datetime(?string $value): string
{
    return fmt_date($value, 'M j, Y · g:i A');
}

function time_ago(?string $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    $seconds = time() - (new DateTimeImmutable($value))->getTimestamp();

    return match (true) {
        $seconds < 60 => 'just now',
        $seconds < 3600 => intdiv($seconds, 60) . 'm ago',
        $seconds < 86400 => intdiv($seconds, 3600) . 'h ago',
        $seconds < 86400 * 7 => intdiv($seconds, 86400) . 'd ago',
        default => fmt_date($value),
    };
}

function plural(int $count, string $singular, ?string $plural = null): string
{
    return $count . ' ' . ($count === 1 ? $singular : ($plural ?? $singular . 's'));
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim(preg_replace('/^Dr\.?\s+/i', '', $name) ?? '')) ?: [];
    $letters = array_map(static fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_filter($parts));

    return implode('', array_slice([reset($letters) ?: '', end($letters) ?: ''], 0, count($letters) > 1 ? 2 : 1));
}

/** Inline SVG icon from the sprite in views/partials/icons.php. */
function icon(string $name, string $class = ''): string
{
    return '<svg class="icon ' . e($class) . '" aria-hidden="true" focusable="false"><use href="#i-' . e($name) . '"></use></svg>';
}

function status_badge(string $status): string
{
    $s = App\Domain\RequestStatus::from($status);

    return '<span class="badge badge-' . $s->tone() . '">' . e($s->label()) . '</span>';
}

/** Field-level error message markup for forms. */
function field_error(string $field, bool $show = true): string
{
    $message = $show ? error($field) : null;

    return $message ? '<span class="field-error">' . e($message) . '</span>' : '';
}

/** `true` when the current path starts with `$path` — for nav highlighting. */
function is_active(string $path): bool
{
    $current = '/' . trim(substr(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', strlen(base_path())), '/');

    return $path === '/' ? $current === '/' : str_starts_with($current, $path);
}
