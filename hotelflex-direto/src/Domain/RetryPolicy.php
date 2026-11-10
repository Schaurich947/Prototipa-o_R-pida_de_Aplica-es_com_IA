<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

use DateTimeImmutable;

/** Exponential backoff: 1, 2, 4 and 8 minutes, then the delivery is marked as failed. */
final class RetryPolicy
{
    public const MAX_ATTEMPTS = 5;

    public static function hasAttemptsLeft(int $attemptsMade): bool
    {
        return $attemptsMade < self::MAX_ATTEMPTS;
    }

    public static function nextAttemptAt(DateTimeImmutable $now, int $attemptsMade): DateTimeImmutable
    {
        $delayMinutes = 2 ** max(0, $attemptsMade - 1);

        return $now->modify("+{$delayMinutes} minutes");
    }
}
