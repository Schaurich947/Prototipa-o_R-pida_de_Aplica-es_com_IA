<?php

use Comprova\Domain\NotificationChannel;
use Comprova\Domain\PhoneNumber;

/**
 * @var Comprova\View\View $view
 * @var NotificationChannel $channel
 * @var Comprova\Domain\Notification|null $notification
 */
?>
<div class="delivery">
    <div class="delivery-head">
        <strong><?= e($channel->label()) ?></strong>
        <?= $view->partial('channel-status', ['notification' => $notification, 'emptyLabel' => 'Sem autorização']) ?>
    </div>
    <?php if ($notification === null): ?>
        <p class="hint">Não enviado: o beneficiário não autorizou avisos por WhatsApp ou não informou o celular.</p>
    <?php else: ?>
        <p class="hint">
            Destino: <?= e($channel === NotificationChannel::Email ? $notification->recipient : PhoneNumber::fromE164Digits($notification->recipient)->format()) ?>
            · tentativas: <?= e($notification->attempts) ?>
            <?= $notification->sentAt ? '· entregue em ' . e(formatDateTime($notification->sentAt)) : '' ?>
        </p>
        <?php if ($notification->lastError): ?>
            <p class="alert alert-error"><?= e($notification->lastError) ?></p>
        <?php endif; ?>
        <?php if ($notification->status->isProblem()): ?>
            <form method="post" action="<?= e(url("/envios/{$notification->id}/reenviar")) ?>">
                <?= $view->partial('csrf-field') ?>
                <button type="submit" class="primary-button small-button">Reenviar <?= e($channel->label()) ?></button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
