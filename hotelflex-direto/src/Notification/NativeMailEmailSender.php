<?php

declare(strict_types=1);

namespace HotelFlex\Notification;

/** Uses PHP mail(), i.e. the server's sendmail/SMTP configured in php.ini (Laragon: Menu > Preferences > Mail Sender). */
final class NativeMailEmailSender implements EmailSender
{
    public function __construct(
        private readonly MimeEmailBuilder $mimeBuilder,
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {
    }

    public function send(EmailMessage $message): DeliveryResult
    {
        $mime = $this->mimeBuilder->build($message, $this->fromEmail, $this->fromName);
        $isAccepted = mail($mime['to'], $mime['subject'], $mime['body'], $mime['headers']);

        return $isAccepted
            ? DeliveryResult::success()
            : DeliveryResult::failure('O servidor de e-mail recusou a mensagem. Verifique a configuração de envio.');
    }

    public function describe(): string
    {
        return 'Envio real via mail() do PHP';
    }
}
