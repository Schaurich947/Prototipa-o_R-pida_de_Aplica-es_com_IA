<?php

declare(strict_types=1);

namespace HotelFlex\Database;

use PDO;
use Throwable;

final class TransactionRunner
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function run(callable $operation): mixed
    {
        $this->connection->beginTransaction();
        try {
            $result = $operation();
            $this->connection->commit();

            return $result;
        } catch (Throwable $error) {
            $this->connection->rollBack();
            throw $error;
        }
    }
}
