<?php

declare(strict_types=1);

// The built-in dev server must serve CSS/JS files directly.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

$projectRoot = dirname(__DIR__);
$composerAutoload = $projectRoot . '/vendor/autoload.php';
require is_file($composerAutoload) ? $composerAutoload : $projectRoot . '/src/autoload.php';

HotelFlex\Bootstrap\App::run($projectRoot);
