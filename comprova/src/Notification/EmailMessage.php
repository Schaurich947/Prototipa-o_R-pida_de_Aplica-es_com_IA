<?php

declare(strict_types=1);

namespace Comprova\Notification;

final readonly class EmailMessage
{
    public function __construct(
        public string $toEmail,
        public string $toName,
        public string $subject,
        public string $textBody,
        public string $attachmentPath,
        public string $attachmentName,
        public string $attachmentMime,
    ) {
    }
}
