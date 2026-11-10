<?php

declare(strict_types=1);

namespace HotelFlex\Notification;

final readonly class DeliveryResult
{
    private function __construct(
        public bool $isSuccess,
        public ?string $providerMessageId,
        public ?string $error,
        /** False for errors that will not fix themselves (e.g. invalid number), so retrying is pointless. */
        public bool $isRetryable,
    ) {
    }

    public static function success(?string $providerMessageId = null): self
    {
        return new self(true, $providerMessageId, null, false);
    }

    public static function failure(string $error, bool $isRetryable = true): self
    {
        return new self(false, null, $error, $isRetryable);
    }
}
