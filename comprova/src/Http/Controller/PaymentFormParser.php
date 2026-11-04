<?php

declare(strict_types=1);

namespace Comprova\Http\Controller;

use Comprova\Domain\DomainException;
use Comprova\Domain\Money;
use Comprova\Domain\PaymentSource;
use Comprova\Http\HttpRequest;
use Comprova\Service\PaymentInput;
use DateTimeImmutable;

/** Server-side reading of the "Registrar pagamento" form; the service validates the business rules. */
final class PaymentFormParser
{
    public static function parse(HttpRequest $request, int $operatorId): PaymentInput
    {
        $paidAt = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $request->getBodyString('paid_at'));
        if ($paidAt === false) {
            throw new DomainException('Informe a data e a hora do pagamento.');
        }
        $description = $request->getBodyString('description');

        return new PaymentInput(
            $request->getBodyString('beneficiary_name'),
            mb_strtolower($request->getBodyString('beneficiary_email')),
            $request->getBodyString('whatsapp_phone'),
            ($request->body['whatsapp_consent'] ?? '') === '1',
            Money::parseToCents($request->getBodyString('amount')),
            $paidAt,
            $description !== '' ? $description : null,
            null,
            PaymentSource::Manual,
            $operatorId,
        );
    }
}
