<?php

declare(strict_types=1);

// Loads app and test classes. Uses Composer when installed; otherwise a minimal PSR-4 loader,
// so the tests also run with a PHPUnit installed elsewhere.
$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;

    return;
}

spl_autoload_register(static function (string $className): void {
    $prefixes = ['Comprova\\Tests\\' => __DIR__ . '/', 'Comprova\\' => dirname(__DIR__) . '/src/'];
    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($className, $prefix)) {
            $classFile = $baseDir . str_replace('\\', '/', substr($className, strlen($prefix))) . '.php';
            if (is_file($classFile)) {
                require $classFile;
            }

            return;
        }
    }
});
