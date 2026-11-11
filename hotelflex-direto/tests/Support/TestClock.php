<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Support;

use DateTimeImmutable;
use HotelFlex\Domain\Clock;

/** Frozen, controllable time: tests jump to deadlines, midnight or breakfast hours. */
final class TestClock implements Clock
{
    private DateTimeImmutable $now;

    public function __construct(string $now)
    {
        $this->now = new DateTimeImmutable($now);
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function travelTo(string $moment): void
    {
        $this->now = new DateTimeImmutable($moment);
    }
}
