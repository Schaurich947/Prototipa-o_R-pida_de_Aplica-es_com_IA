<?php

declare(strict_types=1);

namespace HotelFlex\Notification;

final readonly class EmailMessage
{
    public function __construct(
        public string $toEmail,
        public string $toName,
        public string $subject,
        public string $textBody,
        public ?string $attachmentPath = null,
        public ?string $attachmentName = null,
        public ?string $attachmentMime = null,
    ) {
    }
}
