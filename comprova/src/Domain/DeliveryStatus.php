<?php

declare(strict_types=1);

namespace Comprova\Domain;

/** Overall status of a payment, derived from its notifications. */
enum DeliveryStatus: string
{
    case Delivered = 'delivered';
    case InProgress = 'in_progress';
    case NeedsAttention = 'needs_attention';

    /** @param list<Notification> $notifications */
    public static function fromNotifications(array $notifications): self
    {
        $statuses = array_map(static fn (Notification $notification) => $notification->status, $notifications);
        if (array_filter($statuses, static fn (NotificationStatus $status) => $status->isProblem()) !== []) {
            return self::NeedsAttention;
        }
        if (array_filter($statuses, static fn (NotificationStatus $status) => $status->isInProgress()) !== []) {
            return self::InProgress;
        }

        return self::Delivered;
    }

    public function label(): string
    {
        return match ($this) {
            self::Delivered => 'Entregue',
            self::InProgress => 'Em envio',
            self::NeedsAttention => 'Precisa de atenção',
        };
    }
}
