<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Domain\ServiceRequest;

final readonly class RequestView
{
    public function __construct(
        public ServiceRequest $request,
        public StayView $stay,
    ) {
    }

    /** Fee is recalculated from the hotel rules, never taken from what the client sent. */
    public function feeCents(): int
    {
        return $this->stay->calculateFee($this->request->type, $this->request->extraHours);
    }
}
