<?php

declare(strict_types=1);

namespace HotelFlex\Notification;

use RuntimeException;

/** Development drivers write what would be sent into storage/outbox instead of sending it. */
final class OutboxWriter
{
    public function __construct(private readonly string $outboxPath)
    {
    }

    public function write(string $prefix, string $extension, string $content): string
    {
        if (!is_dir($this->outboxPath) && !mkdir($this->outboxPath, 0750, true) && !is_dir($this->outboxPath)) {
            throw new RuntimeException('Não foi possível criar a pasta de saída de mensagens.');
        }
        $fileName = sprintf('%s-%s-%s.%s', date('Ymd-His'), $prefix, bin2hex(random_bytes(4)), $extension);
        if (file_put_contents("{$this->outboxPath}/{$fileName}", $content) === false) {
            throw new RuntimeException('Não foi possível gravar a mensagem na pasta de saída.');
        }

        return $fileName;
    }
}
