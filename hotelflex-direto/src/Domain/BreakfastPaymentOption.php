<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

/** Breakfast follows the booking rule: paying ahead via Pix earns the discount. Too small to split in two. */
enum BreakfastPaymentOption: string
{
    case Pix = 'pix';
    case Desk = 'desk';

    public function label(): string
    {
        return match ($this) {
            self::Pix => 'Pix agora (100% antecipado)',
            self::Desk => 'Pagar no hotel (dinheiro, cartão ou Pix no balcão)',
        };
    }

    public function discountPercent(): int
    {
        return $this === self::Pix ? PaymentPlan::Full->discountPercent() : 0;
    }

    public function calculateTotal(int $unitPriceCents, int $people): int
    {
        $grossCents = $unitPriceCents * $people;

        return $grossCents - PaymentPlan::calculateDiscount($grossCents, $this->discountPercent());
    }
}
