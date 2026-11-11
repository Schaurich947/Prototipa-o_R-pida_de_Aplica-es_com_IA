<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use DateTimeImmutable;
use HotelFlex\Domain\BreakfastPaymentOption;
use HotelFlex\Domain\BreakfastStatus;
use HotelFlex\Domain\Clock;
use HotelFlex\Domain\Hotel;
use HotelFlex\Domain\User;
use HotelFlex\Repository\BreakfastRepository;

/** Builds the database row of a new breakfast booking: price by option, deadline and code. */
final class BreakfastRowBuilder
{
    public function __construct(
        private readonly BreakfastRepository $bookings,
        private readonly Clock $clock,
    ) {
    }

    /** @return array<string, mixed> */
    public function build(User $customer, Hotel $hotel, DateTimeImmutable $date, int $people, BreakfastPaymentOption $option): array
    {
        $now = $this->clock->now();
        $startsAt = $date->setTime($hotel->breakfastStartHour, 0);
        $dueAt = min($now->modify("+{$hotel->paymentDeadlineHours} hours"), $startsAt);
        $unitPriceCents = $option === BreakfastPaymentOption::WalkIn ? $hotel->breakfastWalkInPriceCents : $hotel->breakfastPriceCents;

        return [
            'code' => $this->generateCode(),
            'user_id' => $customer->id,
            'hotel_id' => $hotel->id,
            'breakfast_date' => $date->format('Y-m-d'),
            'people' => $people,
            'unit_price_cents' => $unitPriceCents,
            'discount_percent' => $option->discountPercent(),
            'total_cents' => $option->calculateTotal($unitPriceCents, $people),
            'payment_option' => $option->value,
            'status' => ($option === BreakfastPaymentOption::Pix ? BreakfastStatus::PendingPayment : BreakfastStatus::Confirmed)->value,
            'due_at' => $option === BreakfastPaymentOption::Pix ? $dueAt->format('Y-m-d H:i:s') : null,
            'created_at' => $now->format('Y-m-d H:i:s'),
        ];
    }

    private function generateCode(): string
    {
        do {
            $code = 'CF-' . strtoupper(bin2hex(random_bytes(3)));
        } while ($this->bookings->codeExists($code));

        return $code;
    }
}
