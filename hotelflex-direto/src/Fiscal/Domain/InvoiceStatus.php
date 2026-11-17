<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

enum InvoiceStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Authorized = 'authorized';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Na fila de emissão',
            self::Processing => 'Em processamento',
            self::Authorized => 'Autorizada',
            self::Rejected => 'Rejeitada',
            self::Cancelled => 'Cancelada',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Authorized => 'badge-success',
            self::Rejected => 'badge-destructive',
            self::Cancelled => 'badge-outline',
            default => 'badge-warning',
        };
    }

    /** Pending, processing and authorized invoices block a second one for the same sale. */
    public function isActive(): bool
    {
        return $this !== self::Rejected && $this !== self::Cancelled;
    }
}
