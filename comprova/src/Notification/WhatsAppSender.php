<?php

declare(strict_types=1);

namespace Comprova\Notification;

interface WhatsAppSender
{
    public function send(WhatsAppMessage $message): DeliveryResult;

    public function describe(): string;
}
