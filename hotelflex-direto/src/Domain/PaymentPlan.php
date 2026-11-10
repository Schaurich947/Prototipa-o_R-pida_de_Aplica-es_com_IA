<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

/** How the guest pays the booking: everything now, or a deposit now and the rest until check-in. */
enum PaymentPlan: string
{
    case Full = 'integral';
    case Deposit = 'sinal';
    case DepositPlusDesk = 'sinal-balcao';

    public const DEPOSIT_PERCENT = 50;

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Pix à vista (100% agora)',
            self::Deposit => 'Pix com sinal (' . self::DEPOSIT_PERCENT . '% agora e o restante via Pix até o check-in)',
            self::DepositPlusDesk => 'Sinal de ' . self::DEPOSIT_PERCENT . '% via Pix + restante no hotel (dinheiro, cartão ou Pix no balcão)',
        };
    }

    /** @return list<int> amounts in cents, in payment order; the parts always add up to the total */
    /** The deposit is always Pix with receipt: it guarantees the booking against no-shows. */
    public function isRestPaidAtDesk(): bool
    {
        return $this === self::DepositPlusDesk;
    }

    public function splitAmount(int $totalCents): array
    {
        if ($this === self::Full) {
            return [$totalCents];
        }
        $deposit = intdiv($totalCents * self::DEPOSIT_PERCENT, 100);

        return [$deposit, $totalCents - $deposit];
    }
}
