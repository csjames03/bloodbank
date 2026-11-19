<?php

declare(strict_types=1);

namespace App\Core;

final class Paginator
{
    public readonly int $pages;

    /** @param list<array<string, mixed>> $items */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {
        $this->pages = max(1, (int) ceil($total / max(1, $perPage)));
    }

    public static function currentPage(mixed $value): int
    {
        return max(1, (int) $value);
    }

    public static function offset(int $page, int $perPage): int
    {
        return ($page - 1) * $perPage;
    }

    public function from(): int
    {
        return $this->total === 0 ? 0 : ($this->page - 1) * $this->perPage + 1;
    }

    public function to(): int
    {
        return min($this->total, $this->page * $this->perPage);
    }

    public function hasPages(): bool
    {
        return $this->pages > 1;
    }
}
