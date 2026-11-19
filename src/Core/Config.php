<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];

    /** @param array<string, mixed> $items */
    public static function set(array $items): void
    {
        self::$items = $items;
    }

    /** Dot-notation lookup, e.g. `Config::get('db.host')`. */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
