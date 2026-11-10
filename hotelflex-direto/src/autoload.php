<?php

declare(strict_types=1);

// Fallback used when `composer install` was not run: the app has no runtime
// packages, so loading its own classes is enough (Composer is only needed for tests).
spl_autoload_register(static function (string $className): void {
    $namespacePrefix = 'HotelFlex\\';
    if (!str_starts_with($className, $namespacePrefix)) {
        return;
    }
    $relativePath = str_replace('\\', '/', substr($className, strlen($namespacePrefix)));
    $classFile = __DIR__ . '/' . $relativePath . '.php';
    if (is_file($classFile)) {
        require $classFile;
    }
});

require_once __DIR__ . '/View/helpers.php';
