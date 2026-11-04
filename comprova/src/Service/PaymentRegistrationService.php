<?php

declare(strict_types=1);

namespace Comprova\Service;

use Comprova\Database\TransactionRunner;
use DateTimeImmutable;
use Throwable;
use Comprova\Domain\Clock;
use Comprova\Domain\DomainException;
use Comprova\Domain\Money;
use Comprova\Domain\NotificationChannel;
use Comprova\Domain\NotificationStatus;
use Comprova\Domain\PaymentSource;
use Comprova\Domain\PhoneNumber;
use Comprova\Repository\BeneficiaryRepository;
use Comprova\Repository\NotificationRepository;
use Comprova\Repository\PaymentRepository;

/**
 * Registers a confirmed payment and queues its deliveries:
 * e-mail with the receipt first, then (with consent) a WhatsApp notice.
 */
final class PaymentRegistrationService
{
    public function __construct(
        private readonly BeneficiaryRepository $beneficiaries,
        private readonly PaymentRepository $payments,
        private readonly NotificationRepository $notifications,
        private readonly ReceiptStorage $receipts,
        private readonly TransactionRunner $transaction,
        private readonly Clock $clock,
    ) {
    }

    /**
     * The receipt is stored only after validation and the duplicate check, and removed
     * again if saving fails, so no orphan files are left behind.
     *
     * @param callable(): StoredReceipt $storeReceipt
     * @return array{paymentId: int, isDuplicate: bool} the same external id never creates a second payment
     */
    public function register(PaymentInput $input, callable $storeReceipt): array
    {
        $existing = $input->externalId !== null ? $this->payments->findByExternalId($input->externalId) : null;
        if ($existing !== null) {
            return ['paymentId' => $existing->id, 'isDuplicate' => true];
        }
        $phone = $this->validate($input);
        $receipt = $storeReceipt();
        try {
            $paymentId = $this->save($input, $receipt, $phone);
        } catch (Throwable $error) {
            $this->receipts->delete($receipt->storedName);
            throw $error;
        }

        return ['paymentId' => $paymentId, 'isDuplicate' => false];
    }

    private function save(PaymentInput $input, StoredReceipt $receipt, ?PhoneNumber $phone): int
    {
        return $this->transaction->run(function () use ($input, $receipt, $phone): int {
            $optInAt = $phone !== null && $input->hasWhatsAppConsent ? $this->clock->now() : null;
            $beneficiaryId = $this->beneficiaries->save($input->beneficiaryName, $input->beneficiaryEmail, $phone, $optInAt);
            $paymentId = $this->payments->create($this->buildPaymentData($input, $receipt, $beneficiaryId));
            $this->queueNotifications($paymentId, $beneficiaryId);

            return $paymentId;
        });
    }

    private function queueNotifications(int $paymentId, int $beneficiaryId): void
    {
        $beneficiary = $this->beneficiaries->findById($beneficiaryId) ?? throw new DomainException('Beneficiário não encontrado.');
        $this->notifications->create($paymentId, NotificationChannel::Email, $beneficiary->email, NotificationStatus::Pending);
        if ($beneficiary->canReceiveWhatsApp()) {
            $this->notifications->create($paymentId, NotificationChannel::WhatsApp, $beneficiary->whatsappPhone->e164Digits, NotificationStatus::Waiting);
        }
    }

    private function validate(PaymentInput $input): ?PhoneNumber
    {
        $nameLength = mb_strlen($input->beneficiaryName);
        if ($nameLength < 2 || $nameLength > 120) {
            throw new DomainException('Informe o nome do beneficiário (2 a 120 caracteres).');
        }
        if (filter_var($input->beneficiaryEmail, FILTER_VALIDATE_EMAIL) === false || strlen($input->beneficiaryEmail) > 190) {
            throw new DomainException('Informe um e-mail válido para o beneficiário.');
        }
        Money::assertValidCents($input->amountCents);
        if ($input->paidAt > $this->clock->now()->modify('+5 minutes')) {
            throw new DomainException('A data do pagamento não pode estar no futuro.');
        }
        if ($input->description !== null && mb_strlen($input->description) > 160) {
            throw new DomainException('A descrição deve ter até 160 caracteres.');
        }

        return $input->whatsappPhone === '' ? null : PhoneNumber::fromBrazilianInput($input->whatsappPhone);
    }

    /** @return array{beneficiaryId: int, externalId: ?string, source: PaymentSource, amountCents: int, description: ?string, paidAt: DateTimeImmutable, receiptPath: string, receiptOriginalName: string, receiptMime: string, createdBy: ?int} */
    private function buildPaymentData(PaymentInput $input, StoredReceipt $receipt, int $beneficiaryId): array
    {
        return [
            'beneficiaryId' => $beneficiaryId,
            'externalId' => $input->externalId,
            'source' => $input->source,
            'amountCents' => $input->amountCents,
            'description' => $input->description,
            'paidAt' => $input->paidAt,
            'receiptPath' => $receipt->storedName,
            'receiptOriginalName' => $receipt->originalName,
            'receiptMime' => $receipt->mime,
            'createdBy' => $input->createdBy,
        ];
    }
}
