<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use DateTimeImmutable;
use HotelFlex\Domain\Charge;
use HotelFlex\Domain\PaymentMethod;

/** Texts of every step of the reservation, for the guest and for the hotel. */
final class ReservationMessages
{
    /** @param list<Charge> $charges the first one confirms the reservation */
    public static function created(StayView $stay, array $charges): Message
    {
        $first = $charges[0];
        $lines = [
            "Sua reserva no {$stay->hotel->name} foi criada para " . self::period($stay) . '.',
            'Para confirmar, pague ' . self::money($first->amountCents) . " ({$first->description}) via Pix para a chave: {$stay->hotel->pixKey}",
            'Depois, envie o comprovante na página da reserva até ' . self::moment($first->dueAt) . '.',
            'Sem o comprovante até esse prazo, a reserva expira e o quarto é liberado.',
        ];
        if (isset($charges[1])) {
            $lines[] = $charges[1]->payAtDesk
                ? 'O restante (' . self::money($charges[1]->amountCents) . ') você paga no hotel, até o check-in: dinheiro, cartão ou Pix no balcão.'
                : 'O restante (' . self::money($charges[1]->amountCents) . ') deve ser pago até ' . self::moment($charges[1]->dueAt) . '.';
        }

        return self::forStay($stay, 'reserva criada · aguardando pagamento', implode("\n", $lines));
    }

    public static function newReservationForHotel(StayView $stay): Message
    {
        return self::forStay($stay, 'nova reserva', "Nova reserva de {$stay->guestName} para " . self::period($stay) . '. Aguardando pagamento via Pix.');
    }

    public static function receiptReceived(StayView $stay, Charge $charge): Message
    {
        return self::forStay($stay, 'comprovante para conferir', "{$stay->guestName} enviou o comprovante de {$charge->description} (" . self::money($charge->amountCents) . '). Confira em Pagamentos.');
    }

    public static function paymentApproved(StayView $stay, Charge $charge, bool $hasConfirmedReservation): Message
    {
        $text = 'Recebemos o seu pagamento de ' . self::money($charge->amountCents) . " ({$charge->description}).";

        return self::forStay($stay, $hasConfirmedReservation ? 'reserva confirmada' : 'pagamento confirmado', $hasConfirmedReservation
            ? $text . "\nSua reserva está confirmada: " . self::period($stay) . '.'
            : $text);
    }

    public static function paymentReceivedAtDesk(StayView $stay, Charge $charge, PaymentMethod $method, bool $hasConfirmedReservation): Message
    {
        $text = 'Recebemos ' . self::money($charge->amountCents) . " ({$charge->description}) por {$method->label()}.";

        return self::forStay($stay, $hasConfirmedReservation ? 'reserva confirmada' : 'pagamento recebido', $hasConfirmedReservation ? $text . "\nSua reserva está confirmada." : $text);
    }

    public static function paymentRejected(StayView $stay, Charge $charge, string $reason): Message
    {
        return self::forStay($stay, 'comprovante não aprovado', "O hotel não aprovou o comprovante de {$charge->description}.\nMotivo: {$reason}\nEnvie um novo comprovante na página da reserva" . ($charge->dueAt ? ' até ' . self::moment($charge->dueAt) : '') . '.');
    }

    public static function newCharge(StayView $stay, int $amountCents, string $description): Message
    {
        return self::forStay($stay, 'nova cobrança', "{$description}: " . self::money($amountCents) . ".\nPague via Pix para {$stay->hotel->pixKey} e envie o comprovante na página da reserva.");
    }

    public static function refundCredit(StayView $stay, int $amountCents): Message
    {
        return self::forStay($stay, 'crédito a devolver', 'Você tem ' . self::money($amountCents) . ' a receber de volta. O hotel fará a devolução pelo Pix usado no pagamento.');
    }

    public static function datesChanged(StayView $stay): Message
    {
        return self::forStay($stay, 'datas alteradas', 'Sua reserva agora é para ' . self::period($stay) . '. Horários flexíveis combinados antes foram zerados.');
    }

    public static function hoursUpdated(StayView $stay): Message
    {
        return self::forStay($stay, 'horários atualizados', 'Entrada: ' . self::moment($stay->startsAt()) . "\nSaída: " . self::moment($stay->endsAt()));
    }

    public static function newRequest(StayView $stay, string $requestLabel, int $hours): Message
    {
        return self::forStay($stay, 'novo pedido de horário', "{$stay->guestName} pediu {$requestLabel} de {$hours} hora(s). Responda em Solicitações.");
    }

    public static function requestRejected(StayView $stay, string $requestLabel): Message
    {
        return self::forStay($stay, 'pedido não aprovado', "O hotel não conseguiu atender o pedido de {$requestLabel}. Seus horários continuam: " . self::moment($stay->startsAt()) . ' a ' . self::moment($stay->endsAt()) . '.');
    }

    public static function cancelled(StayView $stay, int $refundCents): Message
    {
        $refund = $refundCents > 0 ? "\nVocê tem " . self::money($refundCents) . ' a receber de volta.' : '';

        return self::forStay($stay, 'reserva cancelada', 'A reserva foi cancelada.' . $refund);
    }

    public static function expired(StayView $stay): Message
    {
        return self::forStay($stay, 'reserva expirada', 'O prazo para enviar o comprovante terminou e a reserva expirou. Se ainda quiser se hospedar, faça uma nova reserva.');
    }

    public static function roomReleased(StayView $stay): Message
    {
        return self::forStay($stay, 'seu quarto já está pronto!', "O quarto {$stay->room->number} ficou pronto antes do horário. Você já pode fazer o check-in.");
    }

    public static function checkedOut(StayView $stay): Message
    {
        return self::forStay($stay, 'obrigado pela estadia', "Obrigado por se hospedar no {$stay->hotel->name}. Esperamos você de novo!");
    }

    private static function forStay(StayView $stay, string $title, string $body): Message
    {
        return new Message("Reserva {$stay->reservation->code} · {$title}", $body . "\n\n{$stay->hotel->name} · {$stay->hotel->city}");
    }

    private static function period(StayView $stay): string
    {
        return self::moment($stay->startsAt()) . ' a ' . self::moment($stay->endsAt());
    }

    private static function moment(?DateTimeImmutable $moment): string
    {
        return $moment?->format('d/m/Y \à\s H:i') ?? '—';
    }

    private static function money(int $cents): string
    {
        return 'R$ ' . number_format($cents / 100, 2, ',', '.');
    }
}
