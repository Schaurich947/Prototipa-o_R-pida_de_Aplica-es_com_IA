<?php

use Comprova\Domain\DeliveryStatus;
use Comprova\Domain\NotificationChannel;

/**
 * @var Comprova\View\View $view
 * @var Comprova\Service\PaymentRow $row
 * @var bool $canProcessQueue
 */
$payment = $row->payment;
$beneficiary = $row->beneficiary;
$details = [
    'Valor' => formatMoney($payment->amountCents),
    'Pago em' => formatDateTime($payment->paidAt),
    'Origem' => $payment->source->label() . ($payment->externalId ? " · {$payment->externalId}" : ''),
    'Descrição' => $payment->description ?? '—',
    'Beneficiário' => $beneficiary->name,
    'E-mail' => $beneficiary->email,
    'WhatsApp' => $beneficiary->whatsappPhone?->format() ?? 'não informado',
    'Autorização WhatsApp' => $beneficiary->whatsappOptInAt ? 'em ' . formatDateTime($beneficiary->whatsappOptInAt) : 'não autorizado',
];
?>
<a href="<?= e(url('/comprovantes')) ?>">← Comprovantes</a>
<?= $view->partial('page-heading', [
    'eyebrow' => 'Pagamento #' . $payment->id,
    'title' => formatMoney($payment->amountCents) . ' para ' . $beneficiary->name,
    'subtitle' => 'Situação: ' . $row->deliveryStatus->label(),
    'actions' => '<a class="secondary-button" href="' . e(url("/comprovantes/{$payment->id}/arquivo")) . '">Baixar comprovante</a>',
]) ?>

<?php if ($canProcessQueue && $row->deliveryStatus === DeliveryStatus::InProgress): ?>
    <?= $view->partial('process-queue-notice', ['paymentId' => $payment->id]) ?>
<?php endif; ?>

<section class="panel panel-body">
    <dl class="details">
        <?php foreach ($details as $label => $value): ?>
            <div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
        <?php endforeach; ?>
    </dl>
</section>

<section class="panel" aria-labelledby="deliveries-title">
    <div class="panel-heading"><div><h2 id="deliveries-title">Envios</h2><p>O WhatsApp só sai depois que o e-mail com o comprovante é entregue.</p></div></div>
    <?php foreach (NotificationChannel::cases() as $channel): ?>
        <?= $view->partial('delivery-item', ['channel' => $channel, 'notification' => $row->getNotification($channel)]) ?>
    <?php endforeach; ?>
</section>
