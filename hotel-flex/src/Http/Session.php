<?php

declare(strict_types=1);

namespace HotelFlex\Http;

use HotelFlex\Domain\Role;

/**
 * Demo-only sign in: the role is picked on screen. Production must authenticate
 * real users (password or SSO) before storing the role here.
 */
final class Session
{
    public static function start(bool $isHttps): void
    {
        session_name('hotelflex_session');
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'cookie_secure' => $isHttps,
            'use_strict_mode' => true,
        ]);
    }

    public function getUser(): ?SessionUser
    {
        $role = Role::tryFrom((string) ($_SESSION['role'] ?? ''));
        if ($role === null) {
            return null;
        }
        $reservationId = $_SESSION['reservation_id'] ?? null;

        return new SessionUser($role, is_int($reservationId) ? $reservationId : null);
    }

    public function signIn(SessionUser $user): void
    {
        session_regenerate_id(true);
        $_SESSION['role'] = $user->role->value;
        $_SESSION['reservation_id'] = $user->reservationId;
    }

    public function signOut(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public function getCsrfToken(): string
    {
        $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

        return (string) $_SESSION['csrf_token'];
    }

    public function isValidCsrfToken(string $token): bool
    {
        return $token !== '' && hash_equals($this->getCsrfToken(), $token);
    }

    public function flash(string $type, string $message): void
    {
        $_SESSION['flashes'][] = ['type' => $type, 'message' => $message];
    }

    /** @return list<array{type: string, message: string}> */
    public function pullFlashes(): array
    {
        $flashes = $_SESSION['flashes'] ?? [];
        unset($_SESSION['flashes']);

        return is_array($flashes) ? array_values($flashes) : [];
    }
}
