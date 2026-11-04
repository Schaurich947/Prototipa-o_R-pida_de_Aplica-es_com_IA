<?php
/**
 * Shown outside production: the queue can be processed from the screen to test the flow.
 *
 * @var Comprova\View\View $view
 * @var int|null $paymentId
 */
?>
<section class="notice" aria-label="Modo de teste">
    <div>
        <strong>Ambiente de teste</strong>
        <p>Em produção, o agendador roda <code>php bin/send-notifications.php --loop</code>. Aqui você pode processar a fila manualmente.</p>
    </div>
    <form method="post" action="<?= e(url('/envios/processar')) ?>">
        <?= $view->partial('csrf-field') ?>
        <?php if ($paymentId !== null): ?>
            <input type="hidden" name="payment_id" value="<?= e($paymentId) ?>">
        <?php endif; ?>
        <button type="submit" class="secondary-button">Processar envios agora</button>
    </form>
</section>
