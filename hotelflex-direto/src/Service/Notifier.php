<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Domain\User;
use HotelFlex\Repository\NotificationRepository;
use HotelFlex\Repository\UserRepository;

/**
 * Queues messages in the outbox. E-mail always goes; WhatsApp only with recorded consent.
 * Sending happens later (bin/send-notifications.php), so a slow provider never blocks a screen.
 */
final class Notifier
{
    public function __construct(
        private readonly NotificationRepository $notifications,
        private readonly UserRepository $users,
    ) {
    }

    public function notifyGuest(StayView $stay, Message $message): void
    {
        $guest = $this->users->findById($stay->reservation->userId);
        if ($guest !== null) {
            $this->queue($guest, $stay->reservation->id, $message);
        }
    }

    public function notifyHotelStaff(StayView $stay, Message $message): void
    {
        foreach ($this->users->findStaffByHotel($stay->hotel->id) as $staffMember) {
            $this->queue($staffMember, $stay->reservation->id, $message);
        }
    }

    public function notifyUser(int $userId, Message $message): void
    {
        $user = $this->users->findById($userId);
        if ($user !== null) {
            $this->queue($user, null, $message);
        }
    }

    public function notifyHotelStaffById(int $hotelId, Message $message): void
    {
        foreach ($this->users->findStaffByHotel($hotelId) as $staffMember) {
            $this->queue($staffMember, null, $message);
        }
    }

    private function queue(User $recipient, ?int $reservationId, Message $message): void
    {
        $this->notifications->create($reservationId, $recipient->id, 'email', $recipient->email, $message->subject, $message->body);
        if ($recipient->canReceiveWhatsApp()) {
            $this->notifications->create($reservationId, $recipient->id, 'whatsapp', $recipient->whatsappPhone->e164Digits, $message->subject, $message->body);
        }
    }
}
