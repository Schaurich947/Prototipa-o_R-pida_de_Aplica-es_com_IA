<?php

declare(strict_types=1);

namespace HotelFlex\Repository;

use PDO;

/** Exposes a cheap fingerprint of the data so screens can refresh when something changes. */
final class ChangeTrackerRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function getDataVersion(): string
    {
        $statement = $this->connection->query(
            'SELECT CONCAT_WS(\'|\',
                (SELECT MAX(updated_at) FROM rooms),
                (SELECT MAX(updated_at) FROM reservations),
                (SELECT MAX(updated_at) FROM service_requests),
                (SELECT COUNT(*) FROM service_requests),
                (SELECT COUNT(*) FROM reservations))',
        );

        return hash('sha256', (string) $statement->fetchColumn());
    }
}
