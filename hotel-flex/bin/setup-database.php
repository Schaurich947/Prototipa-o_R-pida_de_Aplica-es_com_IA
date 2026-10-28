<?php

declare(strict_types=1);

use HotelFlex\Config\Env;
use HotelFlex\Database\ConnectionFactory;

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
require is_file($composerAutoload) ? $composerAutoload : dirname(__DIR__) . '/src/autoload.php';

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$projectRoot = dirname(__DIR__);
$withSeed = !in_array('--no-seed', $argv, true);

try {
    $env = Env::load($projectRoot . '/.env');
    date_default_timezone_set($env->get('APP_TIMEZONE', 'America/Sao_Paulo'));
    $connection = ConnectionFactory::createAdminConnection($env);
    $connection->exec((string) file_get_contents($projectRoot . '/database/schema.sql'));
    echo "Tabelas criadas.\n";

    if ($withSeed) {
        $connection->exec((string) file_get_contents($projectRoot . '/database/seed.sql'));
        echo "Dados de demonstração inseridos.\n";
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'Falha ao preparar o banco: ' . $error->getMessage() . "\n");
    exit(1);
}
