<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Renders plain-PHP templates from /views inside a layout.
 * Templates receive their data as local variables; layouts receive `$content`.
 */
final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $template, array $data = [], ?string $layout = 'app', int $status = 200): void
    {
        http_response_code($status);
        $content = self::capture($template, $data);

        echo $layout === null
            ? $content
            : self::capture('layouts/' . $layout, [...$data, 'content' => $content]);
    }

    /** @param array<string, mixed> $data */
    public static function capture(string $template, array $data = []): string
    {
        $file = BASE_PATH . '/views/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View [{$template}] not found.");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }
}
