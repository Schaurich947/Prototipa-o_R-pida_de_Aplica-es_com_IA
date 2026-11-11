<?php

declare(strict_types=1);

namespace HotelFlex\Database;

use PDO;
use PDOException;
use Throwable;

final class TransactionRunner
{
    private const MAX_ATTEMPTS = 3;
    /** MySQL/MariaDB codes for "deadlock" and "lock wait timeout": the transaction can simply be retried. */
    private const RETRYABLE_DRIVER_CODES = [1213, 1205];

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
        // Services call each other; an inner call joins the transaction that is already open.
        if ($this->connection->inTransaction()) {
            return $operation();
        }
        for ($attempt = 1; ; $attempt++) {
            try {
                return $this->runOnce($operation);
            } catch (PDOException $error) {
                if ($attempt >= self::MAX_ATTEMPTS || !in_array((int) ($error->errorInfo[1] ?? 0), self::RETRYABLE_DRIVER_CODES, true)) {
                    throw $error;
                }
                usleep(random_int(20_000, 80_000) * $attempt);
            }
        }
    }

    private function runOnce(callable $operation): mixed
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
