<?php

declare(strict_types=1);

namespace Comprova\Http\Controller;

use Comprova\Domain\Clock;
use Comprova\Domain\DomainException;
use Comprova\Domain\Money;
use Comprova\Domain\PaymentSource;
use Comprova\Http\HttpRequest;
use Comprova\Http\Response;
use Comprova\Http\WebhookSignature;
use Comprova\Service\PaymentInput;
use Comprova\Service\PaymentRegistrationService;
use Comprova\Service\ReceiptStorage;
use DateTimeImmutable;
use DateTimeZone;
use Exception;

/** Entry point for the payer system: a confirmed payment arrives here and its receipt is queued for delivery. */
final class WebhookController
{
    /** Base64 grows the 5 MB receipt by about a third, plus the JSON fields. */
    private const MAX_BODY_BYTES = 8 * 1024 * 1024;

    public function __construct(
        private readonly PaymentRegistrationService $registration,
        private readonly ReceiptStorage $receipts,
        private readonly Clock $clock,
        private readonly string $secret,
    ) {
    }

    public function receivePayment(HttpRequest $request): never
    {
        $body = $request->readRawBody(self::MAX_BODY_BYTES);
        if (strlen($body) > self::MAX_BODY_BYTES) {
            Response::json(['error' => 'Requisição muito grande.'], 413);
        }
        $isSigned = WebhookSignature::isValid($this->secret, $request->getHeader('X-Comprova-Timestamp'), $request->getHeader('X-Comprova-Signature'), $body, $this->clock->now()->getTimestamp());
        if (!$isSigned) {
            Response::json(['error' => 'Assinatura inválida ou expirada.'], 401);
        }
        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            Response::json(['error' => 'JSON inválido.'], 400);
        }
        try {
            $result = $this->register($payload);
        } catch (DomainException $error) {
            Response::json(['error' => $error->getMessage()], 422);
        }
        Response::json(['payment_id' => $result['paymentId'], 'duplicate' => $result['isDuplicate']], $result['isDuplicate'] ? 200 : 201);
    }

    /** @param array<string, mixed> $payload @return array{paymentId: int, isDuplicate: bool} */
    private function register(array $payload): array
    {
        $beneficiary = is_array($payload['beneficiary'] ?? null) ? $payload['beneficiary'] : [];
        $receipt = is_array($payload['receipt'] ?? null) ? $payload['receipt'] : [];
        $externalId = self::readString($payload, 'external_id');
        if ($externalId === '' || mb_strlen($externalId) > 80) {
            throw new DomainException('Informe external_id (até 80 caracteres) para evitar pagamentos duplicados.');
        }
        $input = new PaymentInput(
            self::readString($beneficiary, 'name'),
            mb_strtolower(self::readString($beneficiary, 'email')),
            self::readString($beneficiary, 'whatsapp_phone'),
            ($beneficiary['whatsapp_opt_in'] ?? false) === true,
            Money::assertValidCents(is_int($payload['amount_cents'] ?? null) ? $payload['amount_cents'] : 0),
            self::readDate($payload),
            self::readString($payload, 'description') ?: null,
            $externalId,
            PaymentSource::Webhook,
            null,
        );

        return $this->registration->register(
            $input,
            fn () => $this->receipts->storeBase64(self::readString($receipt, 'content_base64'), self::readString($receipt, 'file_name') ?: 'comprovante.pdf'),
        );
    }

    /** @param array<string, mixed> $data */
    private static function readString(array $data, string $key): string
    {
        return is_string($data[$key] ?? null) ? trim($data[$key]) : '';
    }

    /** @param array<string, mixed> $payload */
    private static function readDate(array $payload): DateTimeImmutable
    {
        try {
            return (new DateTimeImmutable(self::readString($payload, 'paid_at') ?: 'invalid'))->setTimezone(new DateTimeZone(date_default_timezone_get()));
        } catch (Exception) {
            throw new DomainException('Informe paid_at no formato ISO 8601, por exemplo 2026-11-03T14:32:00-03:00.');
        }
    }
}
