<?php

declare(strict_types=1);

namespace HotelFlex\Http;

/** "Back to where I was" links accept only known app pages, so a crafted link cannot redirect elsewhere (open redirect). */
final class SafeReturnPath
{
    private const ALLOWED = '#^/(hospede/nova-reserva/revisar|cafe)(\?[A-Za-z0-9_=&%.\-]*)?$#';

    public static function sanitize(string $path): ?string
    {
        return preg_match(self::ALLOWED, $path) ? $path : null;
    }
}
