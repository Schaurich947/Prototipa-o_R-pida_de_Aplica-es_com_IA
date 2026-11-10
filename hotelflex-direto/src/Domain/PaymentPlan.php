<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

/** How the guest pays the booking: everything now, or a deposit now and the rest until check-in. */
enum PaymentPlan: string
{
    case Full = 'integral';
    case Deposit = 'sinal';

    public const DEPOSIT_PERCENT = 50;

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Pix à vista (100% agora)',
            self::Deposit => 'Pix com sinal (' . self::DEPOSIT_PERCENT . '% agora e o restante até o check-in)',
        };
    }

    /** @return list<int> amounts in cents, in payment order; the parts always add up to the total */
    public function splitAmount(int $totalCents): array
    {
        if ($this === self::Full) {
            return [$totalCents];
        }
        $deposit = intdiv($totalCents * self::DEPOSIT_PERCENT, 100);

        return [$deposit, $totalCents - $deposit];
    }
}
