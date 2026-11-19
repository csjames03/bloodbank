<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal `.env` loader. Real environment variables always win over the file.
 */
final class Env
{
    private static bool $loaded = false;

    /** @var array<string, string> */
    private static array $values = [];

    public static function load(string $file): void
    {
        self::$loaded = true;

        if (!is_file($file)) {
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));
            self::$values[$key] = trim($value, "\"'");
        }
    }

    public static function get(string $key, string $default = ''): string
    {
        $env = getenv($key);
        if ($env !== false) {
            return $env;
        }

        return self::$values[$key] ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default ? 'true' : 'false');

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }
}
