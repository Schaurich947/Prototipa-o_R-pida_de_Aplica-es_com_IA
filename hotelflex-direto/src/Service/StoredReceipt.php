<?php

declare(strict_types=1);

namespace HotelFlex\Service;

final readonly class StoredReceipt
{
    public function __construct(
        public string $storedName,
        public string $originalName,
        public string $mime,
    ) {
    }
}
