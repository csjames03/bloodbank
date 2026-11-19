<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session-backed authentication for the two roles: `admin` (blood bank staff)
 * and `hospital` (partner hospital accounts).
 */
final class Auth
{
    public const ADMIN = 'admin';
    public const HOSPITAL = 'hospital';

    /** @return array{role: string, id: int, name: string, username: string}|null */
    public static function user(): ?array
    {
        $user = Session::get('auth');

        return is_array($user) ? $user : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? null) === self::ADMIN;
    }

    public static function isHospital(): bool
    {
        return (self::user()['role'] ?? null) === self::HOSPITAL;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function login(string $role, int $id, string $name, string $username): void
    {
        Session::regenerate();
        Session::put('auth', ['role' => $role, 'id' => $id, 'name' => $name, 'username' => $username]);
    }

    public static function logout(): void
    {
        Session::destroy();
    }
}
