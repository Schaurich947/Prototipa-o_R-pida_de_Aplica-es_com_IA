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
        return self::connect($env, $env->get('DB_USER'), $env->get('DB_PASSWORD', ''));
    }

    public static function createAdminConnection(Env $env): PDO
    {
        return self::connect($env, $env->get('DB_ADMIN_USER'), $env->get('DB_ADMIN_PASSWORD', ''));
    }

    private static function connect(Env $env, string $user, string $password): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $env->get('DB_HOST'),
            $env->get('DB_PORT', '3306'),
            $env->get('DB_NAME'),
        );

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
