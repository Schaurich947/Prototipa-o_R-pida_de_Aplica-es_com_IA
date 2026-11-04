<?php

declare(strict_types=1);

namespace Comprova\Service;

use Comprova\Database\TransactionRunner;
use Comprova\Domain\Clock;
use Comprova\Domain\Notification;
use Comprova\Domain\NotificationChannel;
use Comprova\Domain\RetryPolicy;
use Comprova\Notification\DeliveryResult;
use Comprova\Notification\EmailSender;
use Comprova\Notification\WhatsAppSender;
use Comprova\Repository\BeneficiaryRepository;
use Comprova\Repository\NotificationRepository;
use Comprova\Repository\PaymentRepository;
use Throwable;

/** Processes the delivery queue: sends, retries with backoff and chains WhatsApp after the e-mail. */
final class NotificationDispatcher
{
    private const STUCK_AFTER_MINUTES = 10;
    private const EMAIL_FAILED_REASON = 'Não enviado porque o e-mail com o comprovante falhou.';

    public function __construct(
        private readonly NotificationRepository $notifications,
        private readonly PaymentRepository $payments,
        private readonly BeneficiaryRepository $beneficiaries,
        private readonly ReceiptStorage $receipts,
        private readonly MessageComposer $composer,
        private readonly EmailSender $emailSender,
        private readonly WhatsAppSender $whatsAppSender,
        private readonly TransactionRunner $transaction,
        private readonly Clock $clock,
    ) {
    }

    /** @return array{sent: int, failed: int, retrying: int} */
    public function processDue(int $limit = 20): array
    {
        $now = $this->clock->now();
        $this->notifications->recoverStuck($now->modify('-' . self::STUCK_AFTER_MINUTES . ' minutes'));
        $claimed = $this->transaction->run(fn () => $this->notifications->claimDue($now, $limit));

        $summary = ['sent' => 0, 'failed' => 0, 'retrying' => 0];
        foreach ($claimed as $notification) {
            $summary[$this->deliver($notification)]++;
        }

        return $summary;
    }

    /** @return 'sent'|'failed'|'retrying' */
    private function deliver(Notification $notification): string
    {
        $result = $this->send($notification);
        if ($result->isSuccess) {
            $this->notifications->markSent($notification->id, $result->providerMessageId, $this->clock->now());
            if ($notification->channel === NotificationChannel::Email) {
                $this->notifications->releaseWaiting($notification->paymentId);
            }

            return 'sent';
        }

        return $this->handleFailure($notification, (string) $result->error, $result->isRetryable);
    }

    /** @return 'failed'|'retrying' */
    private function handleFailure(Notification $notification, string $error, bool $isRetryable): string
    {
        $attemptsMade = $notification->attempts + 1;
        if ($isRetryable && RetryPolicy::hasAttemptsLeft($attemptsMade)) {
            $this->notifications->scheduleRetry($notification->id, RetryPolicy::nextAttemptAt($this->clock->now(), $attemptsMade), $error);

            return 'retrying';
        }
        $this->notifications->markFailed($notification->id, $error);
        if ($notification->channel === NotificationChannel::Email) {
            $this->notifications->skipWaiting($notification->paymentId, self::EMAIL_FAILED_REASON);
        }

        return 'failed';
    }

    /** Provider details stay in the server log; the screen gets a short, safe message. */
    private function send(Notification $notification): DeliveryResult
    {
        try {
            $payment = $this->payments->findById($notification->paymentId);
            $beneficiary = $payment ? $this->beneficiaries->findById($payment->beneficiaryId) : null;
            if ($payment === null || $beneficiary === null) {
                return DeliveryResult::failure('Pagamento ou beneficiário não encontrado.', false);
            }

            return $notification->channel === NotificationChannel::Email
                ? $this->emailSender->send($this->composer->composeEmail($payment, $beneficiary, $this->receipts->getAbsolutePath($payment->receiptPath)))
                : $this->whatsAppSender->send($this->composer->composeWhatsApp($payment, $beneficiary, $notification->recipient));
        } catch (Throwable $error) {
            error_log(sprintf('[comprova] envio %d falhou: %s', $notification->id, $error->getMessage()));

            return DeliveryResult::failure('Erro inesperado no envio. Detalhes no log do servidor.');
        }
    }
}
