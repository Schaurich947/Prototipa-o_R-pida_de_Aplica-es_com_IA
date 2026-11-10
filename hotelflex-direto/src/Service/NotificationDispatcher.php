<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Database\TransactionRunner;
use HotelFlex\Domain\Clock;
use HotelFlex\Domain\Notification;
use HotelFlex\Domain\RetryPolicy;
use HotelFlex\Notification\DeliveryResult;
use HotelFlex\Notification\EmailMessage;
use HotelFlex\Notification\EmailSender;
use HotelFlex\Notification\WhatsAppMessage;
use HotelFlex\Notification\WhatsAppSender;
use HotelFlex\Repository\NotificationRepository;
use HotelFlex\Repository\UserRepository;
use Throwable;

/** Sends queued messages with retries and backoff. Run by bin/send-notifications.php. */
final class NotificationDispatcher
{
    private const STUCK_AFTER_MINUTES = 10;

    public function __construct(
        private readonly NotificationRepository $notifications,
        private readonly UserRepository $users,
        private readonly EmailSender $emailSender,
        private readonly WhatsAppSender $whatsAppSender,
        private readonly TransactionRunner $transaction,
        private readonly Clock $clock,
        private readonly string $whatsappTemplate,
        private readonly string $whatsappLanguage,
    ) {
    }

    /** @return array{sent: int, failed: int, retrying: int} */
    public function processDue(int $limit = 30): array
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
            $this->notifications->markSent($notification->id, $this->clock->now());

            return 'sent';
        }
        $attemptsMade = $notification->attempts + 1;
        if ($result->isRetryable && RetryPolicy::hasAttemptsLeft($attemptsMade)) {
            $this->notifications->scheduleRetry($notification->id, RetryPolicy::nextAttemptAt($this->clock->now(), $attemptsMade), (string) $result->error);

            return 'retrying';
        }
        $this->notifications->markFailed($notification->id, (string) $result->error);

        return 'failed';
    }

    /** Provider details stay in the server log; the screen gets a short, safe message. */
    private function send(Notification $notification): DeliveryResult
    {
        try {
            $recipientName = $this->users->findById($notification->userId)?->name ?? 'cliente';
            if ($notification->channel === 'email') {
                return $this->emailSender->send(new EmailMessage($notification->recipient, $recipientName, $notification->subject, "Olá, {$recipientName}.\n\n{$notification->body}"));
            }
            // WhatsApp template parameters cannot contain line breaks.
            $parameters = [$recipientName, $notification->subject, (string) preg_replace('/\s*\n+\s*/', ' · ', trim($notification->body))];
            $preview = "Olá, {$parameters[0]}! {$parameters[1]}: {$parameters[2]}";

            return $this->whatsAppSender->send(new WhatsAppMessage($notification->recipient, $this->whatsappTemplate, $this->whatsappLanguage, $parameters, $preview));
        } catch (Throwable $error) {
            error_log(sprintf('[hotelflex] mensagem %d falhou: %s', $notification->id, $error->getMessage()));

            return DeliveryResult::failure('Erro inesperado no envio. Detalhes no log do servidor.');
        }
    }
}
