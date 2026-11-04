<?php

declare(strict_types=1);

namespace Comprova\Domain;

enum NotificationChannel: string
{
    case Email = 'email';
    case WhatsApp = 'whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'E-mail',
            self::WhatsApp => 'WhatsApp',
        };
    }
}
