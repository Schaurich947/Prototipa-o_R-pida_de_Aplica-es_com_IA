<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
