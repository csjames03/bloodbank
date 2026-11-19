<?php

declare(strict_types=1);

namespace App\Domain;

use App\Core\Auth;

/**
 * Who is performing an action. Passed to services so rules (ownership) and
 * the audit trail don't depend on global session state.
 */
final class Actor
{
    public function __construct(
        public readonly string $role,
        public readonly int $id,
        public readonly string $name,
    ) {
    }

    /** The signed-in user. */
    public static function current(): self
    {
        $user = Auth::user() ?? throw new \LogicException('No signed-in user.');

        return new self($user['role'], (int) $user['id'], $user['name']);
    }

    /** Automatic actions such as expiring units. */
    public static function system(): self
    {
        return new self('system', 0, 'System');
    }

    public function isAdmin(): bool
    {
        return $this->role === Auth::ADMIN;
    }

    public function isHospital(): bool
    {
        return $this->role === Auth::HOSPITAL;
    }

    /** Hospital id when the actor is a hospital, otherwise null. */
    public function hospitalId(): ?int
    {
        return $this->isHospital() ? $this->id : null;
    }
}
