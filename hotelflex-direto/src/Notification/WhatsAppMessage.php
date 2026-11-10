<?php

declare(strict_types=1);

namespace HotelFlex\Notification;

/**
 * Business-initiated WhatsApp messages must use a template approved by Meta;
 * the parameters fill its {{1}}, {{2}}... placeholders.
 */
final readonly class WhatsAppMessage
{
    /** @param list<string> $templateParameters */
    public function __construct(
        public string $toDigits,
        public string $templateName,
        public string $languageCode,
        public array $templateParameters,
        public string $previewText,
    ) {
    }
}
