<?php

declare(strict_types=1);

namespace Comprova\Service;

use Comprova\Domain\DomainException;
use Comprova\Domain\NotificationChannel;
use Comprova\Repository\NotificationRepository;

final class RetryService
{
    public function __construct(private readonly NotificationRepository $notifications)
    {
    }

    /** @return int payment id, to return to its detail page */
    public function retry(int $notificationId): int
    {
        $notification = $this->notifications->findById($notificationId) ?? throw new DomainException('Envio não encontrado.');
        if (!$this->notifications->resetForRetry($notificationId)) {
            throw new DomainException('Só é possível reenviar envios que falharam.');
        }
        if ($notification->channel === NotificationChannel::Email) {
            $this->notifications->rewaitSkipped($notification->paymentId);
        }

        return $notification->paymentId;
    }
}
