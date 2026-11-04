<?php

declare(strict_types=1);

namespace Comprova\Http;

/**
 * The payer system signs each call: hex HMAC-SHA256 of "{timestamp}.{raw body}" with the shared secret.
 * The timestamp window blocks replaying an old captured request.
 */
final class WebhookSignature
{
    public const TOLERANCE_SECONDS = 300;

    public static function sign(string $secret, int $timestamp, string $body): string
    {
        return hash_hmac('sha256', "{$timestamp}.{$body}", $secret);
    }

    public static function isValid(string $secret, string $timestampHeader, string $signatureHeader, string $body, int $now): bool
    {
        if ($secret === '' || !ctype_digit($timestampHeader) || abs($now - (int) $timestampHeader) > self::TOLERANCE_SECONDS) {
            return false;
        }

        return hash_equals(self::sign($secret, (int) $timestampHeader, $body), strtolower($signatureHeader));
    }
}
