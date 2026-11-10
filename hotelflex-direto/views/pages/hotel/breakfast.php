<?php
/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\Hotel $hotel
 * @var list<HotelFlex\Domain\Hotel> $hotelOptions
 * @var DateTimeImmutable $date
 * @var list<HotelFlex\Domain\BreakfastBooking> $bookings
 * @var array<int, string> $customerNames
 * @var int $seatsTaken
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
    <p class="text-sm"><strong><?= e($seatsTaken) ?></strong> de <?= e($hotel->breakfastDailyCapacity) ?> lugares reservados</p>
</form>

<?php if ($bookings === []): ?>
    <?= $view->partial('empty-state', ['message' => 'Nenhum café avulso para este dia.']) ?>
<?php else: ?>
    <div class="grid gap-4 md:grid-cols-2">
        <?php foreach ($bookings as $booking): ?>
            <?= $view->partial('breakfast-hotel-card', ['booking' => $booking, 'customerName' => $customerNames[$booking->userId] ?? 'Cliente', 'date' => $date]) ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
