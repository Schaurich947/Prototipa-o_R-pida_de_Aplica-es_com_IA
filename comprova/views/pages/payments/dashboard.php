<?php

use Comprova\Domain\DeliveryStatus;
use Comprova\Domain\NotificationChannel;

/**
 * @var Comprova\View\View $view
 * @var array{paymentsThisMonth: int, valueThisMonthCents: int, sent: int, inQueue: int, problems: int} $stats
 * @var list<Comprova\Service\PaymentRow> $rows
 * @var DeliveryStatus|null $activeFilter
 * @var bool $canProcessQueue
 */
$statCards = [
    ['label' => 'Pagamentos no mês', 'value' => (string) $stats['paymentsThisMonth'], 'note' => formatMoney($stats['valueThisMonthCents']) . ' processados', 'attention' => false],
    ['label' => 'Envios concluídos', 'value' => (string) $stats['sent'], 'note' => 'E-mails e WhatsApps entregues', 'attention' => false],
    ['label' => 'Na fila', 'value' => (string) $stats['inQueue'], 'note' => 'Aguardando envio ou nova tentativa', 'attention' => false],
    ['label' => 'Precisam de atenção', 'value' => (string) $stats['problems'], 'note' => 'Falharam após as tentativas', 'attention' => $stats['problems'] > 0],
];
$filters = ['' => 'Todos'] + array_column(array_map(static fn ($status) => ['value' => $status->value, 'label' => $status->label()], DeliveryStatus::cases()), 'label', 'value');
?>
<?= $view->partial('page-heading', [
    'eyebrow' => 'Central de pagamentos',
    'title' => 'Comprovantes',
    'subtitle' => 'Acompanhe o envio dos comprovantes por e-mail e o aviso por WhatsApp.',
    'actions' => '<a class="primary-button" href="' . e(url('/comprovantes/novo')) . '">+ Registrar pagamento</a>',
]) ?>

<?php if ($canProcessQueue): ?>
    <?= $view->partial('process-queue-notice', ['paymentId' => null]) ?>
<?php endif; ?>

<section class="stats" aria-label="Resumo de envios">
    <?php foreach ($statCards as $card): ?>
        <article class="stat-card <?= $card['attention'] ? 'attention' : '' ?>">
            <div class="stat-label"><?= e($card['label']) ?></div>
            <div class="stat-value"><?= e($card['value']) ?></div>
            <div class="stat-note"><?= e($card['note']) ?></div>
        </article>
    <?php endforeach; ?>
</section>

<section class="panel" aria-labelledby="table-title">
    <div class="panel-heading">
        <div><h2 id="table-title">Atividade recente</h2><p>Últimos pagamentos e a situação de cada canal de envio.</p></div>
        <nav class="tabs" aria-label="Filtrar por situação">
            <?php foreach ($filters as $value => $label): ?>
                <?php $isActive = ($activeFilter?->value ?? '') === $value; ?>
                <a href="<?= e(url('/comprovantes' . ($value === '' ? '' : "?status={$value}"))) ?>" class="<?= $isActive ? 'active' : '' ?>" <?= $isActive ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
    <div class="table-scroll">
        <table>
            <thead><tr><th>Beneficiário</th><th>Valor</th><th>Pago em</th><th>E-mail</th><th>WhatsApp</th><th><span class="visually-hidden">Ações</span></th></tr></thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="6" class="empty-row">Nenhum pagamento nesta situação.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><div class="person"><span class="person-avatar" aria-hidden="true"><?= e(initials($row->beneficiary->name)) ?></span><span><strong><?= e($row->beneficiary->name) ?></strong><small><?= e($row->beneficiary->email) ?></small></span></div></td>
                        <td class="amount"><?= e(formatMoney($row->payment->amountCents)) ?></td>
                        <td><?= e(formatDateTime($row->payment->paidAt)) ?></td>
                        <td><?= $view->partial('channel-status', ['notification' => $row->getNotification(NotificationChannel::Email), 'emptyLabel' => '—']) ?></td>
                        <td><?= $view->partial('channel-status', ['notification' => $row->getNotification(NotificationChannel::WhatsApp), 'emptyLabel' => 'Sem autorização']) ?></td>
                        <td><a class="secondary-button small-button" href="<?= e(url("/comprovantes/{$row->payment->id}")) ?>">Detalhes</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="lower-grid">
    <?= $view->partial('flow-steps') ?>
    <section class="panel integration-card">
        <div>
            <div class="eyebrow">Automação</div>
            <h2>Conecte seu sistema pagador</h2>
            <p>Com o webhook, cada pagamento confirmado gera o envio sozinho, sem digitação.</p>
        </div>
        <a href="<?= e(url('/integracoes')) ?>">Ver detalhes da integração</a>
    </section>
</div>
