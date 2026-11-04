<?php

declare(strict_types=1);

namespace Comprova\Repository;

use Comprova\Domain\User;
use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findById(int $userId): ?User
    {
        $statement = $this->connection->prepare('SELECT id, name, email FROM users WHERE id = :id');
        $statement->execute(['id' => $userId]);
        $row = $statement->fetch();

        return $row ? User::fromRow($row) : null;
    }

    /** @return array{user: User, passwordHash: string}|null */
    public function findCredentialsByEmail(string $email): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM users WHERE email = :email');
        $statement->execute(['email' => $email]);
        $row = $statement->fetch();

        return $row ? ['user' => User::fromRow($row), 'passwordHash' => (string) $row['password_hash']] : null;
    }

    public function updatePasswordHash(int $userId, string $passwordHash): void
    {
        $this->connection->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')->execute(['hash' => $passwordHash, 'id' => $userId]);
    }
}
