<?php

declare(strict_types=1);

namespace Comprova\Repository;

use PDO;

/** Cheap fingerprint of the data, polled by the screens to refresh delivery statuses. */
final class ChangeTrackerRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function getDataVersion(): string
    {
        $statement = $this->connection->query(
            "SELECT CONCAT_WS('|', (SELECT MAX(updated_at) FROM notifications), (SELECT COUNT(*) FROM payments))",
        );

        return hash('sha256', (string) $statement->fetchColumn());
    }
}
