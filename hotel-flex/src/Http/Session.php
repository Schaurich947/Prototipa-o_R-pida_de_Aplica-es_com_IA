<?php

declare(strict_types=1);

namespace HotelFlex\Http;

/** Wraps $_SESSION: signed-in user id, CSRF token, flash messages and login throttling. */
final class Session
{
    private const MAX_FAILED_LOGINS = 5;
    private const LOCK_SECONDS = 300;

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

    public function getUserId(): ?int
    {
        $userId = $_SESSION['user_id'] ?? null;

        return is_int($userId) ? $userId : null;
    }

    public function signIn(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        unset($_SESSION['failed_logins'], $_SESSION['login_locked_until']);
    }

    public function signOut(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public function isLoginLocked(): bool
    {
        return (int) ($_SESSION['login_locked_until'] ?? 0) > time();
    }

    public function registerFailedLogin(): void
    {
        $_SESSION['failed_logins'] = (int) ($_SESSION['failed_logins'] ?? 0) + 1;
        if ($_SESSION['failed_logins'] >= self::MAX_FAILED_LOGINS) {
            $_SESSION['login_locked_until'] = time() + self::LOCK_SECONDS;
            $_SESSION['failed_logins'] = 0;
        }
    }

    public function get(string $key): mixed
    {
        return $_SESSION['values'][$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION['values'][$key] = $value;
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

    /**
     * Keeps what the user typed when a form fails validation (never the password).
     *
     * @param array<string, mixed> $input
     */
    public function flashOldInput(array $input): void
    {
        $_SESSION['old_input'] = array_diff_key($input, array_flip(['password', 'password_confirmation', 'csrf_token']));
    }

    /** @return array<string, mixed> */
    public function pullOldInput(): array
    {
        $oldInput = $_SESSION['old_input'] ?? [];
        unset($_SESSION['old_input']);

        return is_array($oldInput) ? $oldInput : [];
    }
}
