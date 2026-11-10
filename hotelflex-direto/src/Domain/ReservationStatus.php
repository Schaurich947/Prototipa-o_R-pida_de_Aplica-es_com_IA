<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

enum ReservationStatus: string
{
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case CheckedOut = 'checked_out';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Aguardando pagamento',
            self::Confirmed => 'Confirmada',
            self::CheckedIn => 'Hospedado',
            self::CheckedOut => 'Concluída',
            self::Cancelled => 'Cancelada',
            self::Expired => 'Expirada',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PendingPayment, self::Confirmed, self::CheckedIn], true);
    }
}
