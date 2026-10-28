<?php

declare(strict_types=1);

namespace HotelFlex\Http;

/**
 * Lets the app run at the domain root (php -S) or inside a subfolder such as
 * http://localhost/PROTO_IA_27.10/hotel-flex/ on WAMP/XAMPP.
 */
final class Url
{
    private static string $basePath = '';

    /** @param array<string, mixed> $server */
    public static function detectBasePath(array $server): string
    {
        $scriptDir = rtrim(str_replace('\\', '/', dirname((string) ($server['SCRIPT_NAME'] ?? '/'))), '/');
        $requestPath = (string) parse_url((string) ($server['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

        // Accessed through the root .htaccess, the public URL has no "/public" segment.
        $projectDir = preg_replace('#/public$#', '', $scriptDir) ?? $scriptDir;
        $isInsidePublic = $scriptDir !== $projectDir && self::startsWithSegment($requestPath, $scriptDir);

        return $isInsidePublic ? $scriptDir : $projectDir;
    }

    public static function setBasePath(string $basePath): void
    {
        self::$basePath = rtrim($basePath, '/');
    }

    public static function to(string $path): string
    {
        return self::$basePath . '/' . ltrim($path, '/');
    }

    /** Converts the browser path into the app path used by the router. */
    public static function stripBasePath(string $requestPath): string
    {
        if (self::$basePath === '' || !self::startsWithSegment($requestPath, self::$basePath)) {
            return $requestPath;
        }

        return substr($requestPath, strlen(self::$basePath)) ?: '/';
    }

    private static function startsWithSegment(string $path, string $prefix): bool
    {
        return $path === $prefix || str_starts_with($path, $prefix . '/');
    }
}
