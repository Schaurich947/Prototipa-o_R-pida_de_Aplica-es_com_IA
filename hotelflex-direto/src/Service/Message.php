<?php

declare(strict_types=1);

namespace HotelFlex\Service;

final readonly class Message
{
    public function __construct(
        public string $subject,
        public string $body,
    ) {
    }
}
