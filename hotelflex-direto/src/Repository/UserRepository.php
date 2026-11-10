<?php

declare(strict_types=1);

namespace HotelFlex\Repository;

use DateTimeImmutable;
use HotelFlex\Domain\PhoneNumber;
use HotelFlex\Domain\Role;
use HotelFlex\Domain\User;
use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findById(int $userId): ?User
    {
        $statement = $this->connection->prepare('SELECT id, name, email, role, hotel_id, whatsapp_phone, whatsapp_opt_in_at FROM users WHERE id = :id');
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

    public function emailExists(string $email): bool
    {
        $statement = $this->connection->prepare('SELECT 1 FROM users WHERE email = :email');
        $statement->execute(['email' => $email]);

        return $statement->fetchColumn() !== false;
    }

    public function createGuest(string $name, string $email, string $passwordHash, ?PhoneNumber $phone, ?DateTimeImmutable $optInAt): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO users (name, email, password_hash, role, whatsapp_phone, whatsapp_opt_in_at)
             VALUES (:name, :email, :password_hash, :role, :phone, :opt_in_at)',
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => Role::Guest->value,
            'phone' => $phone?->e164Digits,
            'opt_in_at' => $optInAt?->format('Y-m-d H:i:s'),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /** Removing the phone or the consent stops automatic WhatsApp messages immediately. */
    public function updateWhatsAppContact(int $userId, ?PhoneNumber $phone, ?DateTimeImmutable $optInAt): void
    {
        $statement = $this->connection->prepare('UPDATE users SET whatsapp_phone = :phone, whatsapp_opt_in_at = :opt_in_at WHERE id = :id');
        $statement->execute(['phone' => $phone?->e164Digits, 'opt_in_at' => $optInAt?->format('Y-m-d H:i:s'), 'id' => $userId]);
    }

    /** @return list<User> reception accounts that receive the hotel's alerts */
    public function findStaffByHotel(int $hotelId): array
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, email, role, hotel_id, whatsapp_phone, whatsapp_opt_in_at FROM users WHERE role = :role AND hotel_id = :hotel_id',
        );
        $statement->execute(['role' => Role::Staff->value, 'hotel_id' => $hotelId]);

        return array_map(User::fromRow(...), $statement->fetchAll());
    }

    public function updatePasswordHash(int $userId, string $passwordHash): void
    {
        $statement = $this->connection->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        $statement->execute(['password_hash' => $passwordHash, 'id' => $userId]);
    }

    /** @return list<User> */
    public function findGuests(): array
    {
        $statement = $this->connection->prepare('SELECT id, name, email, role, hotel_id, whatsapp_phone, whatsapp_opt_in_at FROM users WHERE role = :role ORDER BY name');
        $statement->execute(['role' => Role::Guest->value]);

        return array_map(User::fromRow(...), $statement->fetchAll());
    }
}
