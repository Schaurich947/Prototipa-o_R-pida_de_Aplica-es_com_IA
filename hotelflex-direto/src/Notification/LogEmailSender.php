<?php

declare(strict_types=1);

namespace HotelFlex\Notification;

/** Saves the e-mail as an .eml file (opens in any mail client) instead of sending it. */
final class LogEmailSender implements EmailSender
{
    public function __construct(
        private readonly OutboxWriter $outbox,
        private readonly MimeEmailBuilder $mimeBuilder,
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {
    }

    public function send(EmailMessage $message): DeliveryResult
    {
        $mime = $this->mimeBuilder->build($message, $this->fromEmail, $this->fromName);
        $document = "To: {$mime['to']}\r\nSubject: {$mime['subject']}\r\n{$mime['headers']}\r\n\r\n{$mime['body']}";

        return DeliveryResult::success('log:' . $this->outbox->write('email', 'eml', $document));
    }

    public function describe(): string
    {
        return 'Modo teste: e-mails salvos em storage/outbox (.eml)';
    }
}
