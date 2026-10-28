<?php
/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\User $guest
 * @var list<HotelFlex\Domain\User> $guestOptions
 * @var array{current: list<HotelFlex\Service\StayView>, upcoming: list<HotelFlex\Service\StayView>, history: list<HotelFlex\Service\StayView>} $groups
 */
$sections = [
    'current' => ['title' => 'Em andamento', 'empty' => 'Nenhuma hospedagem em andamento.'],
    'upcoming' => ['title' => 'Próximas', 'empty' => 'Nenhuma reserva futura. Que tal planejar a próxima viagem?'],
    'history' => ['title' => 'Histórico', 'empty' => 'Seu histórico aparecerá aqui.'],
];
?>
<?= $view->partial('page-header', [
    'title' => 'Minhas reservas',
    'description' => "Reservas de {$guest->name} em todos os hotéis da plataforma.",
    'actions' => $guestOptions !== []
        ? $view->partial('guest-picker', ['guestOptions' => $guestOptions, 'guest' => $guest, 'action' => '/hospede/reservas'])
        : '<a class="btn btn-primary" href="' . e(url('/hospede/nova-reserva')) . '">Nova reserva</a>',
]) ?>

<?php foreach ($sections as $key => $section): ?>
    <section class="space-y-3">
        <h2 class="text-base font-semibold"><?= e($section['title']) ?> (<?= e(count($groups[$key])) ?>)</h2>
        <?php if ($groups[$key] === []): ?>
            <?= $view->partial('empty-state', ['message' => $section['empty']]) ?>
        <?php else: ?>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($groups[$key] as $stay): ?>
                    <?= $view->partial('stay-card', ['stay' => $stay]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endforeach; ?>
