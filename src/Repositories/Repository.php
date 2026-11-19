<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

abstract class Repository
{
    protected readonly Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /**
     * Escape LIKE wildcards in user search input.
     */
    protected function like(string $term): string
    {
        return '%' . addcslashes($term, '%_\\') . '%';
    }
}
