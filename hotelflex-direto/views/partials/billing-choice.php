<?php
/**
 * "Who is the invoice for?" Asked before every payment; the taker may differ from the person who booked.
 * The hotel must issue an invoice, so a CPF, CNPJ or foreign document is mandatory.
 *
 * @var list<HotelFlex\Fiscal\Domain\BillingProfile> $billingProfiles
 * @var string $billingReturnPath page to come back to after adding new invoice data
 */
$addUrl = url('/faturamento?' . http_build_query(['voltar' => $billingReturnPath]));
?>
<fieldset class="card">
    <legend class="card-header card-title">Nota fiscal em nome de</legend>
    <div class="card-content">
        <?php foreach ($billingProfiles as $index => $profile): ?>
            <label class="flex items-start gap-3 rounded-lg border p-3 text-sm has-[:checked]:border-primary">
                <input type="radio" name="billing_choice" value="<?= e($profile->id) ?>" class="mt-0.5 accent-primary" <?= $index === 0 ? 'checked' : '' ?> required>
                <span>
                    <span class="block font-medium"><?= e($profile->name) ?></span>
                    <span class="text-muted-foreground"><?= e($profile->kind->label()) ?> · <?= e($profile->document->mask()) ?></span>
                </span>
            </label>
        <?php endforeach; ?>
        <?php if ($billingProfiles === []): ?>
            <p class="alert alert-error">Informe o CPF (ou CNPJ) para a nota fiscal antes de continuar: o hotel é obrigado a emiti-la.</p>
            <a class="btn btn-primary btn-sm self-start" href="<?= e($addUrl) ?>">Informar CPF ou CNPJ</a>
        <?php else: ?>
            <a class="btn btn-outline btn-sm self-start" href="<?= e($addUrl) ?>">+ Nota para outra pessoa, empresa (CNPJ) ou estrangeiro</a>
        <?php endif; ?>
        <p class="text-xs text-muted-foreground">A nota é emitida pelo hotel quando o serviço termina (no check-out), com o valor final.</p>
    </div>
</fieldset>
