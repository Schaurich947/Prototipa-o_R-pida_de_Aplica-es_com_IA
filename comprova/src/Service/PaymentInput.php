<?php

declare(strict_types=1);

namespace Comprova\Service;

use Comprova\Domain\PaymentSource;
use DateTimeImmutable;

/** Data needed to register a payment, from the form or from the payer system webhook. */
final readonly class PaymentInput
{
    public function __construct(
        public string $beneficiaryName,
        public string $beneficiaryEmail,
        public string $whatsappPhone,
        public bool $hasWhatsAppConsent,
        public int $amountCents,
        public DateTimeImmutable $paidAt,
        public ?string $description,
        public ?string $externalId,
        public PaymentSource $source,
        public ?int $createdBy,
    ) {
    }
}
