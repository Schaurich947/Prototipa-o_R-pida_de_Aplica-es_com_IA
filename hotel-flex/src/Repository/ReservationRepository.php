<?php

declare(strict_types=1);

namespace HotelFlex\Repository;

use DateTimeImmutable;
use HotelFlex\Domain\Reservation;
use HotelFlex\Domain\ReservationStatus;
use PDO;

final class ReservationRepository
{
    private const NOT_CANCELLED = "status <> 'cancelled'";

    public function __construct(private readonly PDO $connection)
    {
    }

    public function findById(int $reservationId): ?Reservation
    {
        return $this->fetchOne('SELECT * FROM reservations WHERE id = :id', ['id' => $reservationId]);
    }

    /** @return list<Reservation> */
    public function findByUser(int $userId): array
    {
        return $this->fetchAll('SELECT * FROM reservations WHERE user_id = :user_id ORDER BY check_in_date DESC', ['user_id' => $userId]);
    }

    /** Guests arriving on the date plus guests currently in-house, for the hotel panel. @return list<Reservation> */
    public function findCurrentForHotel(int $hotelId, DateTimeImmutable $date): array
    {
        return $this->fetchAll(
            "SELECT res.* FROM reservations res JOIN rooms ON rooms.id = res.room_id
             WHERE rooms.hotel_id = :hotel_id
               AND ((res.status = 'confirmed' AND res.check_in_date = :arrival_date) OR res.status = 'checked_in')",
            ['hotel_id' => $hotelId, 'arrival_date' => $date->format('Y-m-d')],
        );
    }

    /** Non-cancelled stays in the room touching the date range (same-day turnovers included). @return list<Reservation> */
    public function findStaysNear(int $roomId, DateTimeImmutable $fromDate, DateTimeImmutable $toDate, ?int $excludeId = null): array
    {
        return $this->fetchAll(
            'SELECT * FROM reservations WHERE room_id = :room_id AND ' . self::NOT_CANCELLED . "
               AND status <> 'checked_out' AND id <> :exclude_id
               AND check_in_date <= :to_date AND DATE_ADD(check_in_date, INTERVAL nights DAY) >= :from_date",
            ['room_id' => $roomId, 'exclude_id' => $excludeId ?? 0, 'to_date' => $toDate->format('Y-m-d'), 'from_date' => $fromDate->format('Y-m-d')],
        );
    }

    public function findDepartureOn(int $roomId, DateTimeImmutable $date, int $excludeId): ?Reservation
    {
        return $this->fetchOne(
            'SELECT * FROM reservations WHERE room_id = :room_id AND ' . self::NOT_CANCELLED . '
               AND id <> :exclude_id AND DATE_ADD(check_in_date, INTERVAL nights DAY) = :day LIMIT 1',
            ['room_id' => $roomId, 'exclude_id' => $excludeId, 'day' => $date->format('Y-m-d')],
        );
    }

    public function findArrivalOn(int $roomId, DateTimeImmutable $date, int $excludeId): ?Reservation
    {
        return $this->fetchOne(
            'SELECT * FROM reservations WHERE room_id = :room_id AND ' . self::NOT_CANCELLED . '
               AND id <> :exclude_id AND check_in_date = :day LIMIT 1',
            ['room_id' => $roomId, 'exclude_id' => $excludeId, 'day' => $date->format('Y-m-d')],
        );
    }

    public function create(string $code, int $userId, int $roomId, DateTimeImmutable $checkInDate, int $nights): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO reservations (code, user_id, room_id, check_in_date, nights) VALUES (:code, :user_id, :room_id, :check_in_date, :nights)',
        );
        $statement->execute(['code' => $code, 'user_id' => $userId, 'room_id' => $roomId, 'check_in_date' => $checkInDate->format('Y-m-d'), 'nights' => $nights]);

        return (int) $this->connection->lastInsertId();
    }

    /** New dates change the neighbouring stays, so previously agreed flexible hours are reset. */
    public function updateDates(int $reservationId, DateTimeImmutable $checkInDate, int $nights): void
    {
        $this->execute(
            'UPDATE reservations SET check_in_date = :check_in_date, nights = :nights, early_check_in_hours = 0, late_checkout_hours = 0 WHERE id = :id',
            ['check_in_date' => $checkInDate->format('Y-m-d'), 'nights' => $nights, 'id' => $reservationId],
        );
    }

    public function updateEarlyCheckInHours(int $reservationId, int $hours): void
    {
        $this->execute('UPDATE reservations SET early_check_in_hours = :hours WHERE id = :id', ['hours' => $hours, 'id' => $reservationId]);
    }

    public function updateLateCheckoutHours(int $reservationId, int $hours): void
    {
        $this->execute('UPDATE reservations SET late_checkout_hours = :hours WHERE id = :id', ['hours' => $hours, 'id' => $reservationId]);
    }

    public function updateStatus(int $reservationId, ReservationStatus $from, ReservationStatus $to): bool
    {
        $statement = $this->connection->prepare('UPDATE reservations SET status = :to WHERE id = :id AND status = :from');
        $statement->execute(['to' => $to->value, 'id' => $reservationId, 'from' => $from->value]);

        return $statement->rowCount() === 1;
    }

    public function codeExists(string $code): bool
    {
        $statement = $this->connection->prepare('SELECT 1 FROM reservations WHERE code = :code');
        $statement->execute(['code' => $code]);

        return $statement->fetchColumn() !== false;
    }

    /** @param array<string, mixed> $params */
    private function fetchOne(string $sql, array $params): ?Reservation
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        return $row ? Reservation::fromRow($row) : null;
    }

    /** @param array<string, mixed> $params @return list<Reservation> */
    private function fetchAll(string $sql, array $params): array
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return array_map(Reservation::fromRow(...), $statement->fetchAll());
    }

    /** @param array<string, mixed> $params */
    private function execute(string $sql, array $params): void
    {
        $this->connection->prepare($sql)->execute($params);
    }
}
