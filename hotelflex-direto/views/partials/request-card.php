<?php

use HotelFlex\Domain\RequestType;

/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Service\RequestView $requestView
 * @var bool $canResolve
 */
$request = $requestView->request;
$stay = $requestView->stay;
$isEarly = $request->type === RequestType::EarlyCheckIn;
$newHour = $isEarly ? $stay->hotel->checkInHour - $request->extraHours : $stay->hotel->checkOutHour + $request->extraHours;
$feeCents = $requestView->feeCents();
?>
<article class="card py-3">
    <header class="card-header px-3">
        <div>
            <h3 class="card-title text-sm"><?= e($request->type->label()) ?> · <?= e($stay->reservation->code) ?></h3>
            <p class="card-description">
                <?= e($stay->guestName) ?> · Quarto <?= e($stay->room->number) ?> · pedido em <?= e(formatDateTime($request->createdAt)) ?>
            </p>
        </div>
        <?= $view->partial('request-status-badge', ['status' => $request->status]) ?>
    </header>
    <div class="card-content px-3">
        <p class="text-sm">
            <?= $isEarly ? 'Entrar' : 'Sair' ?> às <span class="font-medium"><?= e(formatHour($newHour)) ?></span>
            em <?= e(formatDate($isEarly ? $stay->reservation->checkInDate : $stay->reservation->checkOutDate())) ?>
            (<?= e(formatHours($request->extraHours)) ?> <?= $isEarly ? 'antes' : 'depois' ?>)
            · <span class="font-semibold"><?= $feeCents > 0 ? e(formatMoney($feeCents)) : 'sem custo' ?></span>
        </p>
    </div>
    <?php if ($canResolve && $request->isPending()): ?>
        <footer class="card-footer -mb-3 p-3">
            <form method="post" action="<?= e(url("/hotel/solicitacoes/{$request->id}/aprovar")) ?>">
                <?= $view->partial('csrf-field') ?>
                <button type="submit" class="btn btn-primary btn-sm">Aprovar</button>
            </form>
            <form method="post" action="<?= e(url("/hotel/solicitacoes/{$request->id}/recusar")) ?>">
                <?= $view->partial('csrf-field') ?>
                <button type="submit" class="btn btn-ghost btn-sm">Recusar</button>
            </form>
        </footer>
    <?php endif; ?>
</article>
