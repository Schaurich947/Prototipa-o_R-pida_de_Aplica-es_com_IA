<?php

declare(strict_types=1);

namespace HotelFlex\Http;

use HotelFlex\Domain\Role;

final readonly class SessionUser
{
    public function __construct(
        public Role $role,
        public ?int $reservationId,
    ) {
    }
}
