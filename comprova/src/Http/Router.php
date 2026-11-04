<?php

declare(strict_types=1);

namespace Comprova\Http;

use Closure;

final class Router
{
    public const HOME_PATH = '/comprovantes';

    /** @var list<Route> */
    private array $routes = [];

    public function __construct(
        private readonly Session $session,
        private readonly Auth $auth,
    ) {
    }

    public function get(string $pattern, Closure $handler, bool $requiresAuth = true): void
    {
        $this->routes[] = new Route('GET', $pattern, $handler, $requiresAuth, false);
    }

    public function post(string $pattern, Closure $handler, bool $requiresAuth = true, bool $verifiesCsrf = true): void
    {
        $this->routes[] = new Route('POST', $pattern, $handler, $requiresAuth, $verifiesCsrf);
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
        $isSignedIn = $this->auth->user() !== null;
        if ($route->requiresAuth && !$isSignedIn) {
            Response::redirect('/login');
        }
        if ($route->verifiesCsrf && !$this->session->isValidCsrfToken($request->getBodyString('csrf_token'))) {
            $this->session->flash('error', 'Sessão expirada. Recarregue a página e tente novamente.');
            Response::redirect($isSignedIn ? self::HOME_PATH : '/login');
        }
    }
}
