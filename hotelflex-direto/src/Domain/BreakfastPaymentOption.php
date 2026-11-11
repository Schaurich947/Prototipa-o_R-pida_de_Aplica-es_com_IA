<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

/** Breakfast follows the booking rule: paying ahead via Pix earns the discount. Too small to split in two. */
enum BreakfastPaymentOption: string
{
    case Pix = 'pix';
    case Desk = 'desk';
    /** Sold at the counter on the day, without booking; uses the hotel's higher walk-in price. */
    case WalkIn = 'walk_in';

    public function label(): string
    {
        return match ($this) {
            self::Pix => 'Pix agora (100% antecipado)',
            self::Desk => 'Pagar no hotel (dinheiro, cartão ou Pix no balcão)',
            self::WalkIn => 'Venda na hora (sem reserva)',
        };
    }

    public function discountPercent(): int
    {
        return $this === self::Pix ? PaymentPlan::Full->discountPercent() : 0;
    }

    /** @return list<self> options a customer can pick when booking ahead */
    public static function bookingOptions(): array
    {
        return [self::Pix, self::Desk];
    }

    public function calculateTotal(int $unitPriceCents, int $people): int
    {
        $grossCents = $unitPriceCents * $people;

        return $grossCents - PaymentPlan::calculateDiscount($grossCents, $this->discountPercent());
    }
}
