<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

enum ChargeStatus: string
{
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Paid = 'paid';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case RefundPending = 'refund_pending';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Aguardando pagamento',
            self::UnderReview => 'Comprovante em análise',
            self::Paid => 'Pago',
            self::Rejected => 'Recusado',
            self::Cancelled => 'Cancelada',
            self::RefundPending => 'A devolver',
            self::Refunded => 'Devolvido',
        };
    }

    /** Charges that count towards what the guest owes or already paid. */
    public function countsTowardsTotal(): bool
    {
        return in_array($this, [self::Pending, self::UnderReview, self::Paid], true);
    }
}
