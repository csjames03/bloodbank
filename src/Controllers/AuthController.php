<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Repositories\AccountRepository;

final class AuthController
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 60;

    public function hospitalForm(): void
    {
        View::render('auth/login', ['portal' => Auth::HOSPITAL], 'auth');
    }

    public function adminForm(): void
    {
        View::render('auth/login', ['portal' => Auth::ADMIN], 'auth');
    }

    public function hospitalLogin(Request $request): void
    {
        $this->attempt($request, Auth::HOSPITAL);
    }

    public function adminLogin(Request $request): void
    {
        $this->attempt($request, Auth::ADMIN);
    }

    public function logout(): void
    {
        $wasAdmin = Auth::isAdmin();
        Auth::logout();
        Session::start(); // fresh session so the "signed out" toast survives the redirect
        flash('success', 'You have been signed out.');
        redirect($wasAdmin ? '/admin/login' : '/login');
    }

    private function attempt(Request $request, string $role): void
    {
        $input = $request->all();
        $v = Validator::make($input, [
            'username' => ['required', 'max:50'],
            'password' => ['required', 'max:255'],
        ]);
        if ($v->fails()) {
            back_with_errors($v->errors(), ['username' => $input['username'] ?? '']);
        }

        if ($this->lockedOut()) {
            back_with_errors(
                ['username' => 'Too many failed attempts. Please wait a minute and try again.'],
                ['username' => $input['username']],
            );
        }

        $accounts = new AccountRepository();
        $account = $role === Auth::ADMIN
            ? $accounts->findAdmin($input['username'])
            : $accounts->findHospitalByUsername($input['username']);

        if ($account === null || !password_verify($input['password'], $account['password_hash'])) {
            $this->recordFailure();
            back_with_errors(['username' => 'Incorrect username or password.'], ['username' => $input['username']]);
        }

        Session::forget('login_failures');
        Auth::login($role, (int) $account['id'], $account['full_name'] ?? $account['name'], $account['username']);
        flash('success', 'Welcome back, ' . ($account['full_name'] ?? $account['name']) . '!');
        redirect('/');
    }

    private function lockedOut(): bool
    {
        $failures = array_filter(
            Session::get('login_failures', []),
            static fn (int $at) => $at > time() - self::LOCKOUT_SECONDS,
        );
        Session::put('login_failures', array_values($failures));

        return count($failures) >= self::MAX_ATTEMPTS;
    }

    private function recordFailure(): void
    {
        $failures = Session::get('login_failures', []);
        $failures[] = time();
        Session::put('login_failures', $failures);
    }
}
