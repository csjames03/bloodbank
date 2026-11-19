<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        /** @var array<string, mixed> */
        private readonly array $query,
        /** @var array<string, mixed> */
        private readonly array $body,
    ) {
    }

    public static function capture(string $basePath): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = rawurldecode($path);

        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, strlen('/index.php'));
        }

        $path = '/' . trim($path, '/');

        return new self(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), $path, $_GET, $_POST);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $value = $this->body[$key] ?? $default;

        return is_string($value) ? trim($value) : $value;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        $value = $this->query[$key] ?? $default;

        return is_string($value) ? trim($value) : $value;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return array_map(static fn ($v) => is_string($v) ? trim($v) : $v, $this->body);
    }

    /** @return array<string, mixed> */
    public function queryAll(): array
    {
        return $this->query;
    }
}
