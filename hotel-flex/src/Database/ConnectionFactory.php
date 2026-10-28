<?php

declare(strict_types=1);

namespace HotelFlex\Database;

use HotelFlex\Config\Env;
use DateTimeImmutable;
use PDO;

final class ConnectionFactory
{
    public static function createAppConnection(Env $env): PDO
    {
        return self::connect($env, $env->get('DB_USER'), $env->get('DB_PASSWORD', ''), $env->get('DB_NAME'));
    }

    /** Server-level connection (no database selected), so the installer can create the database. */
    public static function createAdminConnection(Env $env): PDO
    {
        return self::connect($env, $env->get('DB_ADMIN_USER'), $env->get('DB_ADMIN_PASSWORD', ''), null);
    }

    private static function connect(Env $env, string $user, string $password, ?string $databaseName): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $env->get('DB_HOST'), $env->get('DB_PORT', '3306'));
        if ($databaseName !== null) {
            $dsn .= ';dbname=' . $databaseName;
        }

        // Native prepared statements keep parameters typed and out of the SQL text.
        $connection = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        // DATETIME columns are compared with PHP dates, so both must share the app time zone.
        $connection->prepare('SET time_zone = ?')->execute([(new DateTimeImmutable())->format('P')]);

        return $connection;
    }
}
