<?php

use Comprova\Service\ReceiptStorage;

/**
 * @var Comprova\View\View $view
 * @var array<string, mixed> $old
 */
$value = static fn (string $key): string => e($old[$key] ?? '');
$maxMegabytes = ReceiptStorage::MAX_BYTES / 1024 / 1024;
?>
<?= $view->partial('page-heading', [
    'eyebrow' => 'Central de pagamentos',
    'title' => 'Registrar pagamento',
    'subtitle' => 'Para pagamentos feitos fora do sistema pagador. Ao salvar, o comprovante entra na fila de envio.',
    'actions' => null,
]) ?>

<form method="post" action="<?= e(url('/comprovantes')) ?>" enctype="multipart/form-data" class="panel panel-body">
    <?= $view->partial('csrf-field') ?>
    <div class="form-grid">
        <div class="form-section-title">Beneficiário</div>
        <div class="field"><label for="beneficiary_name">Nome</label><input id="beneficiary_name" name="beneficiary_name" type="text" minlength="2" maxlength="120" autocomplete="off" value="<?= $value('beneficiary_name') ?>" required></div>
        <div class="field"><label for="beneficiary_email">E-mail (recebe o comprovante)</label><input id="beneficiary_email" name="beneficiary_email" type="email" maxlength="190" value="<?= $value('beneficiary_email') ?>" required></div>
        <div class="field"><label for="whatsapp_phone">Celular com WhatsApp (opcional)</label><input id="whatsapp_phone" name="whatsapp_phone" type="tel" inputmode="tel" placeholder="(11) 98765-4321" value="<?= $value('whatsapp_phone') ?>"></div>
        <label class="checkbox field">
            <input type="checkbox" name="whatsapp_consent" value="1" <?= ($old['whatsapp_consent'] ?? '') === '1' ? 'checked' : '' ?>>
            <span>O beneficiário autorizou receber avisos de pagamento por WhatsApp. <span class="hint">Sem autorização, só o e-mail é enviado.</span></span>
        </label>

        <div class="form-section-title">Pagamento</div>
        <div class="field"><label for="amount">Valor (R$)</label><input id="amount" name="amount" type="text" inputmode="decimal" placeholder="1.234,56" pattern="[0-9.,]+" value="<?= $value('amount') ?>" required></div>
        <div class="field"><label for="paid_at">Pago em</label><input id="paid_at" name="paid_at" type="datetime-local" max="<?= e(date('Y-m-d\TH:i')) ?>" value="<?= $value('paid_at') ?: e(date('Y-m-d\TH:i')) ?>" required></div>
        <div class="field full"><label for="description">Descrição (opcional)</label><input id="description" name="description" type="text" maxlength="160" placeholder="Ex.: Serviços de outubro" value="<?= $value('description') ?>"></div>
        <div class="field full">
            <label for="receipt">Comprovante</label>
            <input id="receipt" name="receipt" type="file" accept=".pdf,.png,.jpg,.jpeg,application/pdf,image/png,image/jpeg" required>
            <span class="hint">PDF, JPG ou PNG de até <?= e($maxMegabytes) ?> MB. O arquivo fica guardado em área protegida.</span>
        </div>
    </div>
    <div class="form-actions">
        <a class="secondary-button" href="<?= e(url('/comprovantes')) ?>">Cancelar</a>
        <button class="primary-button" type="submit">Registrar e enviar</button>
    </div>
</form>
