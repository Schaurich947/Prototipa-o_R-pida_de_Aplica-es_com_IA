<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

/** How a charge was actually paid. Card data never touches the system: cards go through the hotel's own terminal. */
enum PaymentMethod: string
{
    case PixReceipt = 'pix_receipt';
    case Cash = 'cash';
    case DebitCard = 'debit_card';
    case CreditCard = 'credit_card';
    case PixAtDesk = 'pix_desk';

    public function label(): string
    {
        return match ($this) {
            self::PixReceipt => 'Pix (comprovante)',
            self::Cash => 'Dinheiro no balcão',
            self::DebitCard => 'Cartão de débito no balcão',
            self::CreditCard => 'Cartão de crédito no balcão',
            self::PixAtDesk => 'Pix no balcão',
        };
    }

    /** @return list<self> methods the reception can register at the front desk */
    public static function deskMethods(): array
    {
        return [self::Cash, self::DebitCard, self::CreditCard, self::PixAtDesk];
    }
}
