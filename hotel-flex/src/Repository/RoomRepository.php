<?php

declare(strict_types=1);

namespace HotelFlex\Repository;

use DateTimeImmutable;
use HotelFlex\Domain\Room;
use HotelFlex\Domain\RoomStatus;
use PDO;

final class RoomRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return list<Room> */
    public function findByHotel(int $hotelId): array
    {
        $statement = $this->connection->prepare('SELECT * FROM rooms WHERE hotel_id = :hotel_id ORDER BY number');
        $statement->execute(['hotel_id' => $hotelId]);

        return array_map(Room::fromRow(...), $statement->fetchAll());
    }

    /** @return list<Room> */
    public function findAll(): array
    {
        return array_map(Room::fromRow(...), $this->connection->query('SELECT * FROM rooms ORDER BY hotel_id, number')->fetchAll());
    }

    public function findById(int $roomId): ?Room
    {
        $statement = $this->connection->prepare('SELECT * FROM rooms WHERE id = :id');
        $statement->execute(['id' => $roomId]);
        $row = $statement->fetch();

        return $row ? Room::fromRow($row) : null;
    }

    /**
     * Conditional update: it only succeeds if nobody changed the status meanwhile,
     * which protects concurrent updates from reception and housekeeping.
     */
    public function updateStatus(int $roomId, RoomStatus $from, RoomStatus $to, ?DateTimeImmutable $cleanedAt = null): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE rooms SET status = :to, cleaned_at = COALESCE(:cleaned_at, cleaned_at) WHERE id = :id AND status = :from',
        );
        $statement->execute([
            'to' => $to->value,
            'cleaned_at' => $cleanedAt?->format('Y-m-d H:i:s'),
            'id' => $roomId,
            'from' => $from->value,
        ]);

        return $statement->rowCount() === 1;
    }

    /**
     * Rooms with no active reservation overlapping the nights (date level).
     * The service refines the result with hours and cleaning buffer.
     *
     * @return list<Room>
     */
    public function findFreeForDates(DateTimeImmutable $checkIn, DateTimeImmutable $checkOut, string $city): array
    {
        $statement = $this->connection->prepare(
            "SELECT rooms.* FROM rooms JOIN hotels ON hotels.id = rooms.hotel_id
             WHERE (:city_filter = '' OR hotels.city = :city)
               AND NOT EXISTS (
                   SELECT 1 FROM reservations res
                   WHERE res.room_id = rooms.id AND res.status IN ('confirmed', 'checked_in')
                     AND res.check_in_date < :check_out AND DATE_ADD(res.check_in_date, INTERVAL res.nights DAY) > :check_in
               )
             ORDER BY hotels.name, rooms.daily_rate_cents",
        );
        $statement->execute([
            'city_filter' => $city,
            'city' => $city,
            'check_out' => $checkOut->format('Y-m-d'),
            'check_in' => $checkIn->format('Y-m-d'),
        ]);

        return array_map(Room::fromRow(...), $statement->fetchAll());
    }

    /** Serializes bookings of the same room inside a transaction (prevents double booking). */
    public function lockForUpdate(int $roomId): void
    {
        $this->connection->prepare('SELECT id FROM rooms WHERE id = :id FOR UPDATE')->execute(['id' => $roomId]);
    }
}
