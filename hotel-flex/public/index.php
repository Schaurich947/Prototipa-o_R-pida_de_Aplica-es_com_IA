<?php

declare(strict_types=1);

// The built-in dev server must serve CSS/JS files directly.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require dirname(__DIR__) . '/vendor/autoload.php';

HotelFlex\Bootstrap\App::run(dirname(__DIR__));
