<?php

declare(strict_types=1);

namespace Comprova\Domain;

enum NotificationStatus: string
{
    /** WhatsApp waits for the e-mail, because its text says the receipt is in the inbox. */
    case Waiting = 'waiting';
    case Pending = 'pending';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Aguardando e-mail',
            self::Pending => 'Na fila',
            self::Sending => 'Enviando',
            self::Sent => 'Enviado',
            self::Failed => 'Falhou',
            self::Skipped => 'Não enviado',
        };
    }

    public function isInProgress(): bool
    {
        return in_array($this, [self::Waiting, self::Pending, self::Sending], true);
    }

    public function isProblem(): bool
    {
        return $this === self::Failed || $this === self::Skipped;
    }
}
