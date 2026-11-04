<?php

declare(strict_types=1);

namespace Comprova\Tests\Domain;

use Comprova\Domain\DeliveryStatus;
use Comprova\Domain\Notification;
use Comprova\Domain\NotificationChannel;
use Comprova\Domain\NotificationStatus;
use Comprova\Domain\RetryPolicy;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DeliveryRulesTest extends TestCase
{
    public function testRetriesWithExponentialBackoffUntilTheLimit(): void
    {
        $now = new DateTimeImmutable('2026-11-03 10:00');

        $this->assertSame('10:01', RetryPolicy::nextAttemptAt($now, 1)->format('H:i'));
        $this->assertSame('10:08', RetryPolicy::nextAttemptAt($now, 4)->format('H:i'));
        $this->assertTrue(RetryPolicy::hasAttemptsLeft(4));
        $this->assertFalse(RetryPolicy::hasAttemptsLeft(5));
    }

    public function testAnyFailedChannelNeedsAttention(): void
    {
        $status = DeliveryStatus::fromNotifications([$this->notification(NotificationStatus::Sent), $this->notification(NotificationStatus::Failed)]);

        $this->assertSame(DeliveryStatus::NeedsAttention, $status);
    }

    public function testWhatsAppWaitingForEmailIsStillInProgress(): void
    {
        $status = DeliveryStatus::fromNotifications([$this->notification(NotificationStatus::Sent), $this->notification(NotificationStatus::Waiting)]);

        $this->assertSame(DeliveryStatus::InProgress, $status);
    }

    public function testAllSentIsDelivered(): void
    {
        $this->assertSame(DeliveryStatus::Delivered, DeliveryStatus::fromNotifications([$this->notification(NotificationStatus::Sent)]));
    }

    private function notification(NotificationStatus $status): Notification
    {
        return new Notification(1, 1, NotificationChannel::Email, 'a@b.com', $status, 1, null, null);
    }
}
