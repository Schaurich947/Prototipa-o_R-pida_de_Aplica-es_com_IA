<?php

declare(strict_types=1);

// Processes the delivery queue. Run once:      php bin/send-notifications.php
// Or keep it running (worker):                 php bin/send-notifications.php --loop
// In production, schedule it (cron / Windows Task Scheduler) or run --loop under a process supervisor.

use HotelFlex\Bootstrap\Container;
use HotelFlex\Config\Env;
use HotelFlex\Database\ConnectionFactory;
use HotelFlex\Http\Session;
use HotelFlex\View\View;

$projectRoot = dirname(__DIR__);
$composerAutoload = $projectRoot . '/vendor/autoload.php';
require is_file($composerAutoload) ? $composerAutoload : $projectRoot . '/src/autoload.php';

if (PHP_SAPI !== 'cli') {
    exit(1);
}

const LOOP_INTERVAL_SECONDS = 15;

$env = Env::load($projectRoot . '/.env');
date_default_timezone_set($env->get('APP_TIMEZONE', 'America/Sao_Paulo'));
$container = new Container(ConnectionFactory::createAppConnection($env), $env, new Session(), new View($projectRoot . '/views'), $projectRoot);
$isLoop = in_array('--loop', $argv, true);

do {
    try {
        $expired = $container->chargeService()->expireOverdue() + $container->breakfastService()->expireOverdue();
        $summary = $container->dispatcher()->processDue();
        printf("[%s] reservas expiradas: %d · mensagens enviadas: %d · nova tentativa: %d · falhas: %d\n", date('H:i:s'), $expired, $summary['sent'], $summary['retrying'], $summary['failed']);
    } catch (Throwable $error) {
        fwrite(STDERR, '[hotelflex] erro ao processar a fila: ' . $error->getMessage() . "\n");
    }
    if ($isLoop) {
        sleep(LOOP_INTERVAL_SECONDS);
    }
} while ($isLoop);
