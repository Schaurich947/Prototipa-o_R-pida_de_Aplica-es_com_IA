<?php

use HotelFlex\Domain\PaymentPlan;

/**
 * Tells customers they may also buy breakfast on the spot, if seats are left, at a higher price.
 *
 * @var list<HotelFlex\Domain\Hotel> $hotels
 */
$walkInHotels = array_values(array_filter($hotels, static fn ($hotel) => $hotel->sellsWalkInBreakfast()));
?>
<?php if ($walkInHotels !== []): ?>
    <aside class="rounded-lg border border-dashed p-4 text-sm" aria-label="Venda no local">
        <p class="font-medium">Sem reserva? Também vendemos na hora, se houver lugar.</p>
        <ul class="mt-1 space-y-0.5 text-muted-foreground">
            <?php foreach ($walkInHotels as $hotel): ?>
                <li>
                    <?= e($hotel->name) ?>: <?= e(formatMoney($hotel->breakfastWalkInPriceCents)) ?>/pessoa no balcão ·
                    reservando: <?= e(formatMoney($hotel->breakfastPriceCents)) ?>, ou
                    <?= e(formatMoney($hotel->breakfastPriceCents - PaymentPlan::calculateDiscount($hotel->breakfastPriceCents, PaymentPlan::Full->discountPercent()))) ?> com Pix antecipado
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="mt-1 text-xs text-muted-foreground">Hóspedes do hotel têm prioridade. A venda na hora não é garantida: depende dos lugares livres no dia.</p>
    </aside>
<?php endif; ?>
