<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Admin and hospital accounts.
 */
final class AccountRepository extends Repository
{
    /** @return array<string, mixed>|null */
    public function findAdmin(string $username): ?array
    {
        return $this->db->one('SELECT * FROM admins WHERE lower(username) = lower(?)', [$username]);
    }

    /** @return array<string, mixed>|null */
    public function findHospitalByUsername(string $username): ?array
    {
        return $this->db->one('SELECT * FROM hospitals WHERE lower(username) = lower(?)', [$username]);
    }

    /** @return array<string, mixed>|null */
    public function findHospital(int $id): ?array
    {
        return $this->db->one('SELECT id, name, username, address, contact FROM hospitals WHERE id = ?', [$id]);
    }

    /** @return list<array<string, mixed>> */
    public function hospitals(): array
    {
        return $this->db->all('SELECT id, name, address FROM hospitals ORDER BY name');
    }
}
