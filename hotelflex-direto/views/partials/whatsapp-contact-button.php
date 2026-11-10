<?php
/**
 * Free click-to-chat link between guest and hotel, with the reservation code filled in.
 *
 * @var HotelFlex\Service\StayView $stay
 * @var bool $toGuest true on the hotel side (talk to the guest)
 */
$phone = $toGuest ? $stay->guestPhone : $stay->hotel->whatsappPhone;
$text = $toGuest
    ? "Olá, {$stay->guestName}! Aqui é do {$stay->hotel->name}, sobre a sua reserva {$stay->reservation->code}."
    : "Olá! Sou {$stay->guestName}, da reserva {$stay->reservation->code}.";
?>
<?php if ($phone !== null): ?>
    <a class="btn btn-whatsapp btn-sm" href="<?= e(whatsappLink($phone, $text)) ?>" target="_blank" rel="noopener noreferrer">
        <?= $toGuest ? 'WhatsApp do hóspede' : 'Falar com o hotel no WhatsApp' ?>
    </a>
<?php endif; ?>
