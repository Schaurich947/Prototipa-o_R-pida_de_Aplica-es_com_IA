<?php

declare(strict_types=1);

namespace Comprova\Http;

use Closure;

final readonly class Route
{
    private string $regex;

    /** @param Closure(HttpRequest, array<string, int>): void $handler */
    public function __construct(
        public string $method,
        public string $pattern,
        public Closure $handler,
        public bool $requiresAuth,
        /** Webhooks authenticate with an HMAC signature instead of the session CSRF token. */
        public bool $verifiesCsrf,
    ) {
        $this->regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>\d+)', $pattern) . '$#';
    }

    /** @return array<string, int>|null */
    public function match(string $method, string $path): ?array
    {
        if ($method !== $this->method || !preg_match($this->regex, $path, $matches)) {
            return null;
        }

        return array_map('intval', array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
    }
}
