<?php

declare(strict_types=1);

use HotelFlex\Config\Env;
use HotelFlex\Database\ConnectionFactory;
use HotelFlex\Database\DatabaseInstaller;

$projectRoot = dirname(__DIR__);
$composerAutoload = $projectRoot . '/vendor/autoload.php';
require is_file($composerAutoload) ? $composerAutoload : $projectRoot . '/src/autoload.php';

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$envFile = $projectRoot . '/.env';
if (!is_file($envFile)) {
    fwrite(STDERR, "Arquivo .env não encontrado em {$projectRoot}. Copie o .env.example para .env e preencha.\n");
    exit(1);
}

try {
    $env = Env::load($envFile);
    date_default_timezone_set($env->get('APP_TIMEZONE', 'America/Sao_Paulo'));
    $installer = new DatabaseInstaller(ConnectionFactory::createAdminConnection($env), $env, $projectRoot . '/database');
    foreach ($installer->install(!in_array('--no-seed', $argv, true)) as $message) {
        echo $message . "\n";
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'Falha ao preparar o banco: ' . $error->getMessage() . "\n");
    exit(1);
}
