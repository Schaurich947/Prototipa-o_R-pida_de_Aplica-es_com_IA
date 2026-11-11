<?php
/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\Hotel $hotel
 * @var list<HotelFlex\Domain\Hotel> $hotelOptions
 * @var DateTimeImmutable $date
 * @var list<HotelFlex\Domain\BreakfastBooking> $bookings
 * @var array<int, string> $customerNames
 * @var HotelFlex\Domain\BreakfastAvailability $availability
 * @var bool $isToday
 * @var bool $hasEndedToday
 */
$hotelPicker = renderIf($hotelOptions !== [], fn () => $view->partial('select-picker', [
    'action' => '/hotel/cafe', 'name' => 'hotel', 'label' => 'Hotel',
    'options' => toOptions($hotelOptions, static fn ($option) => $option->name),
    'selectedId' => $hotel->id,
]));
?>
<?= $view->partial('page-header', [
    'title' => "Café da manhã · {$hotel->name}",
    'description' => $hotel->sellsBreakfast()
        ? 'Cafés avulsos (clientes não hospedados) · ' . formatMoney($hotel->breakfastPriceCents) . '/pessoa · ' . formatHour($hotel->breakfastStartHour) . ' às ' . formatHour($hotel->breakfastEndHour)
        : 'Este hotel não vende café da manhã avulso.',
    'actions' => $hotelPicker,
]) ?>

<form method="get" action="<?= e(url('/hotel/cafe')) ?>" class="flex flex-wrap items-end gap-3">
    <div class="space-y-2">
        <label class="label" for="breakfast-day">Dia</label>
        <input class="input" type="date" id="breakfast-day" name="data" value="<?= e($date->format('Y-m-d')) ?>">
    </div>
    <button type="submit" class="btn btn-outline">Ver dia</button>
</form>

<section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Lugares do dia">
    <?= $view->partial('stat-card', ['label' => 'Capacidade', 'value' => (string) $availability->capacity]) ?>
    <?= $view->partial('stat-card', ['label' => "Hóspedes ({$availability->occupiedRooms} quarto(s) × {$hotel->breakfastSeatsPerRoom} lugares)", 'value' => (string) $availability->guestSeats]) ?>
    <?= $view->partial('stat-card', ['label' => 'Avulsos reservados/vendidos', 'value' => (string) $availability->bookedSeats]) ?>
    <?= $view->partial('stat-card', ['label' => 'Lugares livres', 'value' => (string) $availability->freeSeats]) ?>
</section>

<?php if ($isToday && $hotel->sellsWalkInBreakfast()): ?>
    <?php if ($hasEndedToday): ?>
        <p class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">Venda na hora: o café de hoje já terminou (até as <?= e(formatHour($hotel->breakfastEndHour)) ?>).</p>
    <?php else: ?>
        <?= $view->partial('breakfast-walk-in-form', ['hotel' => $hotel, 'freeSeats' => $availability->freeSeats]) ?>
    <?php endif; ?>
<?php endif; ?>

<?php if ($bookings === []): ?>
    <?= $view->partial('empty-state', ['message' => 'Nenhum café avulso para este dia.']) ?>
<?php else: ?>
    <div class="grid gap-4 md:grid-cols-2">
        <?php foreach ($bookings as $booking): ?>
            <?= $view->partial('breakfast-hotel-card', ['booking' => $booking, 'customerName' => $booking->walkInName ?? ($booking->paymentOption->value === 'walk_in' ? 'Cliente no balcão' : ($customerNames[$booking->userId] ?? 'Cliente')), 'date' => $date]) ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
