<?php

declare(strict_types=1);

namespace HotelFlex\Notification;

use HotelFlex\Config\Env;

/** Chooses each channel driver from .env (MAIL_DRIVER, WHATSAPP_DRIVER). Defaults to the safe "log" mode. */
final class SenderFactory
{
    public function __construct(
        private readonly Env $env,
        private readonly string $outboxPath,
    ) {
    }

    public function createEmailSender(): EmailSender
    {
        $fromEmail = $this->env->get('MAIL_FROM_ADDRESS', 'nao-responda@hotelflex.test');
        $fromName = $this->env->get('MAIL_FROM_NAME', $this->env->get('COMPANY_NAME', 'HotelFlex Direto'));
        if ($this->env->get('MAIL_DRIVER', 'log') === 'mail') {
            return new NativeMailEmailSender(new MimeEmailBuilder(), $fromEmail, $fromName);
        }

        return new LogEmailSender(new OutboxWriter($this->outboxPath), new MimeEmailBuilder(), $fromEmail, $fromName);
    }

    public function createWhatsAppSender(): WhatsAppSender
    {
        if ($this->env->get('WHATSAPP_DRIVER', 'log') === 'cloud_api') {
            return new CloudApiWhatsAppSender($this->env->get('WHATSAPP_PHONE_NUMBER_ID'), $this->env->get('WHATSAPP_ACCESS_TOKEN'));
        }

        return new LogWhatsAppSender(new OutboxWriter($this->outboxPath));
    }

    public function getWhatsAppTemplate(): string
    {
        return $this->env->get('WHATSAPP_TEMPLATE_NAME', 'atualizacao_reserva');
    }

    public function getWhatsAppLanguage(): string
    {
        return $this->env->get('WHATSAPP_TEMPLATE_LANGUAGE', 'pt_BR');
    }
}
