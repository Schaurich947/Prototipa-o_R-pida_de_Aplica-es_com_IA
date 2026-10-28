<?php

declare(strict_types=1);

namespace HotelFlex\Repository;

use HotelFlex\Domain\Hotel;
use PDO;

final class HotelRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return list<Hotel> */
    public function findAll(): array
    {
        return array_map(Hotel::fromRow(...), $this->connection->query('SELECT * FROM hotels ORDER BY name')->fetchAll());
    }

    public function findById(int $hotelId): ?Hotel
    {
        $statement = $this->connection->prepare('SELECT * FROM hotels WHERE id = :id');
        $statement->execute(['id' => $hotelId]);
        $row = $statement->fetch();

        return $row ? Hotel::fromRow($row) : null;
    }

    /** @return list<string> */
    public function findCities(): array
    {
        return $this->connection->query('SELECT DISTINCT city FROM hotels ORDER BY city')->fetchAll(PDO::FETCH_COLUMN);
    }
}
