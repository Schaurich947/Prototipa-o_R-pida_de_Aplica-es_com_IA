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
    public function findAll(): array
    {
        $statement = $this->connection->query('SELECT * FROM rooms ORDER BY number');

        return array_map(Room::fromRow(...), $statement->fetchAll());
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
            'UPDATE rooms SET status = :to, cleaned_at = COALESCE(:cleaned_at, cleaned_at)
             WHERE id = :id AND status = :from',
        );
        $statement->execute([
            'to' => $to->value,
            'cleaned_at' => $cleanedAt?->format('Y-m-d H:i:s'),
            'id' => $roomId,
            'from' => $from->value,
        ]);

        return $statement->rowCount() === 1;
    }
}
