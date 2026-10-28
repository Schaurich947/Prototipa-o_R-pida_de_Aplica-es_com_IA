<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

final readonly class User
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public Role $role,
        /** Only staff accounts belong to a hotel. */
        public ?int $hotelId,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['email'],
            Role::from((string) $row['role']),
            $row['hotel_id'] !== null ? (int) $row['hotel_id'] : null,
        );
    }

    public function canManageHotel(int $hotelId): bool
    {
        return $this->role === Role::Admin || ($this->role === Role::Staff && $this->hotelId === $hotelId);
    }

    public function canAccessReservation(Reservation $reservation): bool
    {
        return $this->role === Role::Admin || $reservation->userId === $this->id;
    }
}
