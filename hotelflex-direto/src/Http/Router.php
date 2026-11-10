<?php

declare(strict_types=1);

namespace HotelFlex\Http;

use Closure;
use HotelFlex\Domain\Role;

final class Router
{
    /** @var list<Route> */
    private array $routes = [];

    public function __construct(
        private readonly Session $session,
        private readonly Auth $auth,
    ) {
    }

    /** @param list<Role>|null $roles */
    public function get(string $pattern, Closure $handler, ?array $roles): void
    {
        $this->routes[] = new Route('GET', $pattern, $handler, $roles);
    }

    /** @param list<Role>|null $roles */
    public function post(string $pattern, Closure $handler, ?array $roles): void
    {
        $this->routes[] = new Route('POST', $pattern, $handler, $roles);
    }

    /** Returns false when no route matched, so the caller can render a 404. */
    public function dispatch(HttpRequest $request): bool
    {
        foreach ($this->routes as $route) {
            $params = $route->match($request->method, $request->path);
            if ($params === null) {
                continue;
            }
            $this->guard($route, $request);
            ($route->handler)($request, $params);

            return true;
        }

        return false;
    }

    private function guard(Route $route, HttpRequest $request): void
    {
        $user = $this->auth->user();
        if (!$route->allows($user)) {
            Response::redirect($user ? Navigation::getHomePath($user->role) : '/login');
        }
        if ($request->method === 'POST' && !$this->session->isValidCsrfToken($request->getBodyString('csrf_token'))) {
            $this->session->flash('error', 'Sessão expirada. Recarregue a página e tente novamente.');
            Response::redirect($user ? Navigation::getHomePath($user->role) : '/login');
        }
    }
}
