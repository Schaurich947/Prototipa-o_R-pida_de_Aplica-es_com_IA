<?php
/**
 * Every automatic message about this reservation (e-mail and WhatsApp).
 *
 * @var HotelFlex\View\View $view
 * @var list<HotelFlex\Domain\Notification> $messages
 */
?>
<section class="space-y-3">
    <h2 class="text-base font-semibold">Mensagens enviadas</h2>
    <?php if ($messages === []): ?>
        <?= $view->partial('empty-state', ['message' => 'Nenhuma mensagem ainda.']) ?>
    <?php else: ?>
        <ul class="space-y-2">
            <?php foreach ($messages as $message): ?>
                <li class="rounded-lg border p-3 text-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="font-medium"><?= e($message->subject) ?></span>
                        <span class="text-xs text-muted-foreground"><?= e($message->channelLabel()) ?> · <?= e($message->statusLabel()) ?> · <?= e(formatDateTime($message->createdAt)) ?></span>
                    </div>
                    <p class="mt-1 whitespace-pre-line text-muted-foreground"><?= e($message->body) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
