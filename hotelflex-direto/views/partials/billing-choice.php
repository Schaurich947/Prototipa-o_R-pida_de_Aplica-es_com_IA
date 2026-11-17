<?php

use HotelFlex\Fiscal\Service\BillingProfileService;

/**
 * "Who is the invoice for?" Asked before every payment; the taker may differ from the person who booked.
 *
 * @var list<HotelFlex\Fiscal\Domain\BillingProfile> $billingProfiles
 * @var bool $allowsAnonymous
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
        <?php if ($allowsAnonymous): ?>
            <label class="flex items-start gap-3 rounded-lg border p-3 text-sm has-[:checked]:border-primary">
                <input type="radio" name="billing_choice" value="<?= e(BillingProfileService::ANONYMOUS_CHOICE) ?>" class="mt-0.5 accent-primary" <?= $billingProfiles === [] ? 'checked' : '' ?> required>
                <span>
                    <span class="block font-medium">Consumidor, sem CPF na nota</span>
                    <span class="text-muted-foreground">A nota sai sem identificação do tomador.</span>
                </span>
            </label>
        <?php elseif ($billingProfiles === []): ?>
            <p class="alert alert-error">Este hotel exige CPF ou CNPJ na nota. Cadastre os dados antes de continuar.</p>
        <?php endif; ?>
        <a class="btn btn-outline btn-sm self-start" href="<?= e($addUrl) ?>">+ Nota para outra pessoa, empresa (CNPJ) ou estrangeiro</a>
        <p class="text-xs text-muted-foreground">A nota é emitida pelo hotel quando o serviço termina (no check-out), com o valor final.</p>
    </div>
</fieldset>
