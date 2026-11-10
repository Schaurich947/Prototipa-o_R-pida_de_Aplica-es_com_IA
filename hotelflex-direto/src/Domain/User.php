<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

use DateTimeImmutable;

final readonly class User
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public Role $role,
        /** Only staff accounts belong to a hotel. */
        public ?int $hotelId,
        public ?PhoneNumber $whatsappPhone = null,
        public ?DateTimeImmutable $whatsappOptInAt = null,
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
            ($row['whatsapp_phone'] ?? null) !== null ? PhoneNumber::fromE164Digits((string) $row['whatsapp_phone']) : null,
            ($row['whatsapp_opt_in_at'] ?? null) !== null ? new DateTimeImmutable((string) $row['whatsapp_opt_in_at']) : null,
        );
    }

    /** Automatic WhatsApp messages require a phone and the recorded consent. */
    public function canReceiveWhatsApp(): bool
    {
        return $this->whatsappPhone !== null && $this->whatsappOptInAt !== null;
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
