<?php

declare(strict_types=1);

namespace Comprova\Service;

use Comprova\Domain\Beneficiary;
use Comprova\Domain\DeliveryStatus;
use Comprova\Domain\Notification;
use Comprova\Domain\NotificationChannel;
use Comprova\Domain\Payment;

/** One line of the activity table: payment, beneficiary and the status of each channel. */
final readonly class PaymentRow
{
    public DeliveryStatus $deliveryStatus;

    /** @param list<Notification> $notifications */
    public function __construct(
        public Payment $payment,
        public Beneficiary $beneficiary,
        public array $notifications,
    ) {
        $this->deliveryStatus = DeliveryStatus::fromNotifications($notifications);
    }

    public function getNotification(NotificationChannel $channel): ?Notification
    {
        foreach ($this->notifications as $notification) {
            if ($notification->channel === $channel) {
                return $notification;
            }
        }

        return null;
    }
}
