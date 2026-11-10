<?php

declare(strict_types=1);

namespace HotelFlex\Notification;

interface EmailSender
{
    public function send(EmailMessage $message): DeliveryResult;

    public function describe(): string;
}
