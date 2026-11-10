<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

enum BreakfastStatus: string
{
    case PendingPayment = 'pending_payment';
    case UnderReview = 'under_review';
    case Confirmed = 'confirmed';
    case Served = 'served';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Aguardando Pix',
            self::UnderReview => 'Comprovante em análise',
            self::Confirmed => 'Confirmado',
            self::Served => 'Atendido',
            self::Cancelled => 'Cancelado',
            self::Expired => 'Expirado',
        };
    }

    /** Statuses that take a seat from the day's capacity. */
    public function holdsSeat(): bool
    {
        return in_array($this, [self::PendingPayment, self::UnderReview, self::Confirmed, self::Served], true);
    }
}
