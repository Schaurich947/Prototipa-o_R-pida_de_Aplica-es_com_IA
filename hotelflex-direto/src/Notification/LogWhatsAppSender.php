<?php

declare(strict_types=1);

namespace HotelFlex\Notification;

/** Saves the exact Cloud API payload as JSON instead of sending it. */
final class LogWhatsAppSender implements WhatsAppSender
{
    public function __construct(private readonly OutboxWriter $outbox)
    {
    }

    public function send(WhatsAppMessage $message): DeliveryResult
    {
        $document = [
            'preview' => $message->previewText,
            'payload' => CloudApiWhatsAppSender::buildPayload($message),
        ];
        $json = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return DeliveryResult::success('log:' . $this->outbox->write('whatsapp', 'json', $json));
    }

    public function describe(): string
    {
        return 'Modo teste: mensagens salvas em storage/outbox (.json)';
    }
}
