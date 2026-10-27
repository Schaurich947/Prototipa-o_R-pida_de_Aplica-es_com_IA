<?php

declare(strict_types=1);

namespace HotelFlex\Http;

use Closure;
use HotelFlex\Domain\Role;

final readonly class Route
{
    private string $regex;

    /**
     * @param Closure(HttpRequest, array<string, int>): void $handler
     * @param list<Role>|null $allowedRoles null means public
     */
    public function __construct(
        public string $method,
        public string $pattern,
        public Closure $handler,
        public ?array $allowedRoles,
    ) {
        $this->regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>\d+)', $pattern) . '$#';
    }

    /** @return array<string, int>|null */
    public function match(string $method, string $path): ?array
    {
        if ($method !== $this->method || !preg_match($this->regex, $path, $matches)) {
            return null;
        }
        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

        return array_map('intval', $params);
    }

    public function allows(?SessionUser $user): bool
    {
        if ($this->allowedRoles === null) {
            return true;
        }

        return $user !== null && in_array($user->role, $this->allowedRoles, true);
    }
}
