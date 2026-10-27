<?php

declare(strict_types=1);

namespace HotelFlex\Repository;

use HotelFlex\Domain\Reservation;
use PDO;

final class ReservationRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return list<Reservation> */
    public function findAll(): array
    {
        $statement = $this->connection->query('SELECT * FROM reservations ORDER BY code');

        return array_map(Reservation::fromRow(...), $statement->fetchAll());
    }

    public function findById(int $reservationId): ?Reservation
    {
        $statement = $this->connection->prepare('SELECT * FROM reservations WHERE id = :id');
        $statement->execute(['id' => $reservationId]);
        $row = $statement->fetch();

        return $row ? Reservation::fromRow($row) : null;
    }

    public function findByRoomId(int $roomId): ?Reservation
    {
        $statement = $this->connection->prepare('SELECT * FROM reservations WHERE room_id = :room_id LIMIT 1');
        $statement->execute(['room_id' => $roomId]);
        $row = $statement->fetch();

        return $row ? Reservation::fromRow($row) : null;
    }

    public function updateLateCheckoutHours(int $reservationId, int $hours): void
    {
        $statement = $this->connection->prepare('UPDATE reservations SET late_checkout_hours = :hours WHERE id = :id');
        $statement->execute(['hours' => $hours, 'id' => $reservationId]);
    }
}
