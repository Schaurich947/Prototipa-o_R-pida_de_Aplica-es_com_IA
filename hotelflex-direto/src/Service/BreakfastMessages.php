<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Domain\BreakfastBooking;
use HotelFlex\Domain\Hotel;

/** Texts of the breakfast booking steps. */
final class BreakfastMessages
{
    public static function created(BreakfastBooking $booking, Hotel $hotel): Message
    {
        $payment = $booking->dueAt !== null
            ? "Pague " . self::money($booking->totalCents) . " via Pix para {$hotel->pixKey} e envie o comprovante até {$booking->dueAt->format('d/m/Y \à\s H:i')}."
            : 'Pagamento no hotel: ' . self::money($booking->totalCents) . ' (dinheiro, cartão ou Pix no balcão).';

        return self::message($booking, $hotel, 'reservado', self::when($booking, $hotel) . "\n" . $payment);
    }

    public static function forStaff(BreakfastBooking $booking, Hotel $hotel, string $guestName): Message
    {
        return self::message($booking, $hotel, 'novo café avulso', "{$guestName} reservou café para {$booking->people} pessoa(s) em {$booking->date->format('d/m/Y')}.");
    }

    public static function confirmed(BreakfastBooking $booking, Hotel $hotel): Message
    {
        return self::message($booking, $hotel, 'confirmado', 'Pagamento recebido. ' . self::when($booking, $hotel));
    }

    public static function rejected(BreakfastBooking $booking, Hotel $hotel, string $reason): Message
    {
        return self::message($booking, $hotel, 'comprovante não aprovado', "Motivo: {$reason}\nEnvie um novo comprovante na página do café.");
    }

    public static function cancelled(BreakfastBooking $booking, Hotel $hotel): Message
    {
        $refund = $booking->isPaid ? "\nO hotel devolverá " . self::money($booking->totalCents) . ' pelo Pix usado no pagamento.' : '';

        return self::message($booking, $hotel, 'cancelado', 'O café da manhã foi cancelado.' . $refund);
    }

    public static function expired(BreakfastBooking $booking, Hotel $hotel): Message
    {
        return self::message($booking, $hotel, 'expirado', 'O comprovante não foi enviado no prazo e os lugares foram liberados.');
    }

    private static function when(BreakfastBooking $booking, Hotel $hotel): string
    {
        return "{$booking->people} pessoa(s) em {$booking->date->format('d/m/Y')}, das " . sprintf('%02d:00 às %02d:00', $hotel->breakfastStartHour, $hotel->breakfastEndHour) . '.';
    }

    private static function message(BreakfastBooking $booking, Hotel $hotel, string $title, string $body): Message
    {
        return new Message("Café da manhã {$booking->code} · {$title}", $body . "\n\n{$hotel->name} · {$hotel->city}");
    }

    private static function money(int $cents): string
    {
        return 'R$ ' . number_format($cents / 100, 2, ',', '.');
    }
}
