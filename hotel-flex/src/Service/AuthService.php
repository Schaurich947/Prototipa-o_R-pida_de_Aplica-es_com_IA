<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\User;
use HotelFlex\Repository\UserRepository;

final class AuthService
{
    public const MIN_PASSWORD_LENGTH = 8;

    public function __construct(private readonly UserRepository $users)
    {
    }

    /** Same message for unknown e-mail and wrong password, so accounts cannot be discovered. */
    public function authenticate(string $email, string $password): User
    {
        $credentials = $this->users->findCredentialsByEmail(self::normalizeEmail($email));
        if ($credentials === null || !password_verify($password, $credentials['passwordHash'])) {
            throw new DomainException('E-mail ou senha inválidos.');
        }
        if (password_needs_rehash($credentials['passwordHash'], PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash($credentials['user']->id, password_hash($password, PASSWORD_DEFAULT));
        }

        return $credentials['user'];
    }

    public function registerGuest(string $name, string $email, string $password, string $passwordConfirmation): User
    {
        $email = self::normalizeEmail($email);
        $this->assertValidRegistration($name, $email, $password, $passwordConfirmation);
        if ($this->users->emailExists($email)) {
            throw new DomainException('Já existe uma conta com este e-mail.');
        }
        $userId = $this->users->createGuest($name, $email, password_hash($password, PASSWORD_DEFAULT));

        return $this->users->findById($userId) ?? throw new DomainException('Não foi possível criar a conta.');
    }

    private function assertValidRegistration(string $name, string $email, string $password, string $confirmation): void
    {
        $nameLength = mb_strlen($name);
        if ($nameLength < 2 || $nameLength > 120) {
            throw new DomainException('Informe seu nome (2 a 120 caracteres).');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 190) {
            throw new DomainException('Informe um e-mail válido.');
        }
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new DomainException('A senha deve ter pelo menos ' . self::MIN_PASSWORD_LENGTH . ' caracteres.');
        }
        if (!hash_equals($password, $confirmation)) {
            throw new DomainException('A confirmação de senha não confere.');
        }
    }

    private static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
