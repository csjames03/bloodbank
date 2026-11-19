<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Tiny router: exact paths plus `{id}` numeric parameters, per-route guards
 * and automatic CSRF verification for every POST.
 *
 * Guards: `guest`, `auth`, `admin`, `hospital`.
 */
final class Router
{
    /** @var list<array{method: string, regex: string, handler: array{class-string, string}, guard: ?string}> */
    private array $routes = [];

    /** @param array{class-string, string} $handler */
    public function get(string $path, array $handler, ?string $guard = null): void
    {
        $this->add('GET', $path, $handler, $guard);
    }

    /** @param array{class-string, string} $handler */
    public function post(string $path, array $handler, ?string $guard = null): void
    {
        $this->add('POST', $path, $handler, $guard);
    }

    /** @param array{class-string, string} $handler */
    private function add(string $method, string $path, array $handler, ?string $guard): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>\d+)', $path) . '$#';
        $this->routes[] = compact('method', 'regex', 'handler', 'guard');
    }

    public function dispatch(Request $request): void
    {
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path, $matches)) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $request->method) {
                continue;
            }

            if ($request->method === 'POST' && !Csrf::verify($request->input('_token'))) {
                flash('error', 'Your session expired. Please try again.');
                redirect_back('/');
            }

            $this->guard($route['guard']);

            $params = array_map('intval', array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
            [$class, $method] = $route['handler'];
            (new $class())->{$method}($request, ...$params);

            return;
        }

        throw $pathMatched ? new HttpException(405, 'Method not allowed') : HttpException::notFound();
    }

    private function guard(?string $guard): void
    {
        match ($guard) {
            null => null,
            'guest' => Auth::check() ? redirect('/') : null,
            'auth' => Auth::check() ? null : redirect('/login'),
            'admin' => match (true) {
                !Auth::check() => redirect('/admin/login'),
                !Auth::isAdmin() => throw HttpException::forbidden(),
                default => null,
            },
            'hospital' => match (true) {
                !Auth::check() => redirect('/login'),
                !Auth::isHospital() => throw HttpException::forbidden(),
                default => null,
            },
        };
    }
}
