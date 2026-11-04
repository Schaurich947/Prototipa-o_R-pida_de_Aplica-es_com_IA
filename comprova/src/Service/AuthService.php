<?php

declare(strict_types=1);

namespace Comprova\Service;

use Comprova\Domain\DomainException;
use Comprova\Domain\User;
use Comprova\Repository\UserRepository;

final class AuthService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    /** Same message for unknown e-mail and wrong password, so accounts cannot be discovered. */
    public function authenticate(string $email, string $password): User
    {
        $credentials = $this->users->findCredentialsByEmail(mb_strtolower(trim($email)));
        if ($credentials === null || !password_verify($password, $credentials['passwordHash'])) {
            throw new DomainException('E-mail ou senha inválidos.');
        }
        if (password_needs_rehash($credentials['passwordHash'], PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash($credentials['user']->id, password_hash($password, PASSWORD_DEFAULT));
        }

        return $credentials['user'];
    }
}
