<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Performance;

use DateTimeImmutable;
use PDO;

/**
 * Bulk data for the volume test: many hotels and rooms, a back-to-back history of 2-night stays
 * per room (past, current and future), one paid charge per stay and a queue of pending messages.
 */
final class VolumeDataGenerator
{
    private const STAYS_PER_ROOM = 40;
    private const GUESTS = 1000;
    private const HEAVY_GUEST_STAYS = 200;
    private const QUEUED_MESSAGES = 2000;
    private const BATCH = 1000;

    public function __construct(private readonly PDO $admin)
    {
    }

    /** @return array<string, int> */
    public function generate(int $hotelCount, int $roomsPerHotel): array
    {
        $this->admin->exec('DELETE FROM rooms WHERE id > 0');
        $this->insertHotels($hotelCount);
        $rooms = $this->insertRooms($hotelCount, $roomsPerHotel);
        $this->insertGuests();
        $reservations = $this->insertReservations($rooms);
        $this->admin->exec("INSERT INTO charges (reservation_id, kind, description, amount_cents, status, created_at, paid_method)
            SELECT id, 'booking', 'Diárias da reserva', 64000, 'paid', NOW(), 'pix_receipt' FROM reservations");
        $this->insertQueuedMessages();

        return [
            'hotels' => $hotelCount + 2,
            'rooms' => count($rooms),
            'users' => self::GUESTS + 6,
            'reservations' => $reservations,
            'heavyGuestReservations' => self::HEAVY_GUEST_STAYS,
            'charges' => $reservations,
            'notifications' => self::QUEUED_MESSAGES,
        ];
    }

    private function insertHotels(int $hotelCount): void
    {
        $rows = [];
        for ($index = 3; $index < $hotelCount + 3; $index++) {
            $rows[] = sprintf("(%d, 'Hotel Volume %d', 'Cidade %d', 14, 12, 8, 18, 1, 1, 'pix@volume.test', NULL, 24, 48)", $index, $index, $index % 5);
        }
        $this->insertInBatches('INSERT INTO hotels (id, name, city, check_in_hour, check_out_hour, earliest_check_in_hour, latest_check_out_hour, early_free_hours, cleaning_buffer_hours, pix_key, whatsapp_phone, payment_deadline_hours, free_cancellation_hours) VALUES ', $rows);
    }

    /** @return list<int> room ids (hotel 1 is the "busiest hotel" measured by the panel) */
    private function insertRooms(int $hotelCount, int $roomsPerHotel): array
    {
        $rows = [];
        $roomId = 0;
        foreach ([1, ...range(3, $hotelCount + 2)] as $hotelId) {
            for ($number = 1; $number <= $roomsPerHotel; $number++) {
                $rows[] = sprintf("(%d, %d, '%d', 'Standard', 32000, 5000, 'clean')", ++$roomId, $hotelId, 100 + $number);
            }
        }
        $this->insertInBatches('INSERT INTO rooms (id, hotel_id, number, category, daily_rate_cents, hourly_rate_cents, status) VALUES ', $rows);

        return range(1, $roomId);
    }

    private function insertGuests(): void
    {
        $hash = '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W';
        $rows = array_map(static fn (int $index) => sprintf("('Hóspede %d', 'hospede%d@volume.test', '%s', 'guest')", $index, $index, $hash), range(1, self::GUESTS));
        $this->insertInBatches('INSERT INTO users (name, email, password_hash, role) VALUES ', $rows);
    }

    /** Back-to-back 2-night stays every 3 days, from two months before to two months after "today". */
    private function insertReservations(array $rooms): int
    {
        $start = new DateTimeImmutable('2026-10-02');
        $today = new DateTimeImmutable('2026-12-01');
        $guestIds = $this->admin->query("SELECT id FROM users WHERE role = 'guest'")->fetchAll(PDO::FETCH_COLUMN);
        $rows = [];
        $sequence = 0;
        foreach ($rooms as $roomId) {
            for ($stay = 0; $stay < self::STAYS_PER_ROOM; $stay++) {
                $checkIn = $start->modify('+' . ($stay * 3) . ' days');
                $status = $checkIn->modify('+2 days') < $today ? 'checked_out' : ($checkIn <= $today ? 'checked_in' : 'confirmed');
                $userId = $sequence < self::HEAVY_GUEST_STAYS ? 4 : $guestIds[$sequence % count($guestIds)];
                $rows[] = sprintf("('VL-%07d', %d, %d, '%s', 2, '%s', 'integral', 5)", ++$sequence, $userId, $roomId, $checkIn->format('Y-m-d'), $status);
            }
        }
        $this->insertInBatches('INSERT INTO reservations (code, user_id, room_id, check_in_date, nights, status, payment_plan, discount_percent) VALUES ', $rows);

        return $sequence;
    }

    private function insertQueuedMessages(): void
    {
        $rows = array_map(static fn (int $index) => "(4, 'email', 'ana@hotelflex.test', 'Mensagem de volume {$index}', 'Teste de volume')", range(1, self::QUEUED_MESSAGES));
        $this->insertInBatches('INSERT INTO notifications (user_id, channel, recipient, subject, body) VALUES ', $rows);
    }

    /** @param list<string> $rows already-formatted value tuples built from internal data only */
    private function insertInBatches(string $insertPrefix, array $rows): void
    {
        foreach (array_chunk($rows, self::BATCH) as $chunk) {
            $this->admin->exec($insertPrefix . implode(',', $chunk));
        }
    }
}
