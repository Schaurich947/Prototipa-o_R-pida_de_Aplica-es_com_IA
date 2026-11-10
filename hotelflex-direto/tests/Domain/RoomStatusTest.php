<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Domain;

use HotelFlex\Domain\RoomStatus;
use PHPUnit\Framework\TestCase;

final class RoomStatusTest extends TestCase
{
    public function testHousekeepingFollowsTheExpectedOrder(): void
    {
        $this->assertTrue(RoomStatus::Dirty->canTransitionTo(RoomStatus::Cleaning));
        $this->assertTrue(RoomStatus::Cleaning->canTransitionTo(RoomStatus::Clean));
        $this->assertTrue(RoomStatus::Clean->canTransitionTo(RoomStatus::Released));
    }

    public function testDirtyRoomCannotBeReleasedDirectly(): void
    {
        $this->assertFalse(RoomStatus::Dirty->canTransitionTo(RoomStatus::Released));
    }
}
