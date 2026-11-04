<?php

declare(strict_types=1);

namespace Comprova\Http;

use Comprova\Domain\DomainException;
use Comprova\Domain\User;
use Comprova\Repository\UserRepository;

/** The user is reloaded from the database on every request, so role changes apply immediately. */
final class Auth
{
    private ?User $user = null;
    private bool $isLoaded = false;

    public function __construct(
        private readonly Session $session,
        private readonly UserRepository $users,
    ) {
    }

    public function user(): ?User
    {
        if (!$this->isLoaded) {
            $userId = $this->session->getUserId();
            $this->user = $userId ? $this->users->findById($userId) : null;
            $this->isLoaded = true;
        }

        return $this->user;
    }

    public function requireUser(): User
    {
        return $this->user() ?? throw new DomainException('Sua sessão expirou. Entre novamente.');
    }
}
