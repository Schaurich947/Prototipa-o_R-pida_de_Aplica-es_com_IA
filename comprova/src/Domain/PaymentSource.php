<?php

declare(strict_types=1);

namespace Comprova\Domain;

enum PaymentSource: string
{
    case Manual = 'manual';
    case Webhook = 'webhook';

    public function label(): string
    {
        return $this === self::Manual ? 'Registro manual' : 'Sistema pagador';
    }
}
