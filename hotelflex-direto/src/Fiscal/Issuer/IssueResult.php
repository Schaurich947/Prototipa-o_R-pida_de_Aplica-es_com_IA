<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Issuer;

/** Answer of the tax authority (or of the simulation) to an issue, query or cancellation. */
final readonly class IssueResult
{
    private function __construct(
        public bool $isAuthorized,
        /** True for outages and timeouts: the invoice goes back to the queue with backoff. */
        public bool $isRetryable,
        public ?string $accessKey,
        public ?string $message,
    ) {
    }

    public static function authorized(string $accessKey): self
    {
        return new self(true, false, $accessKey, null);
    }

    public static function rejected(string $reason): self
    {
        return new self(false, false, null, $reason);
    }

    public static function unavailable(string $reason): self
    {
        return new self(false, true, null, $reason);
    }
}
