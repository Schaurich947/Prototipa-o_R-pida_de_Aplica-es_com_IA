<?php

use HotelFlex\Fiscal\Domain\BillingKind;

/**
 * New invoice data. A company CNPJ can be checked first: the Receita Federal data fills the form.
 *
 * @var HotelFlex\View\View $view
 * @var string|null $returnTo
 * @var array<string, mixed> $old
 * @var HotelFlex\Fiscal\Domain\CompanyRecord|null $company
 * @var string|null $lookupMessage
 */
$value = static fn (string $key): string => (string) ($old[$key] ?? '');
$address = $company?->address;
$addressValue = static fn (string $key, ?string $fromCompany): string => $value($key) !== '' ? $value($key) : (string) $fromCompany;
$selectedKind = BillingKind::tryFrom($value('kind')) ?? BillingKind::Self;
?>
<section class="card">
    <header class="card-header">
        <div>
            <h2 class="card-title">Novo cadastro</h2>
            <p class="card-description">Empresa? Consulte o CNPJ primeiro: razão social e endereço vêm da Receita Federal.</p>
        </div>
    </header>
    <div class="card-content">
        <form method="get" action="<?= e(url('/faturamento')) ?>" class="flex flex-col gap-2 sm:flex-row">
            <?php if ($returnTo !== null): ?><input type="hidden" name="voltar" value="<?= e($returnTo) ?>"><?php endif; ?>
            <label class="sr-only" for="lookup-cnpj">CNPJ para consultar</label>
            <input class="input" id="lookup-cnpj" name="consultar_cnpj" placeholder="CNPJ (números ou letras)" maxlength="18" value="<?= e($company?->cnpj ?? $value('document')) ?>" required>
            <button type="submit" class="btn btn-outline">Consultar CNPJ</button>
        </form>
        <?php if ($lookupMessage !== null): ?>
            <p class="alert alert-error"><?= e($lookupMessage) ?></p>
        <?php elseif ($company !== null): ?>
            <p class="alert <?= $company->isActive() ? 'alert-success' : 'alert-error' ?>">
                <?= e($company->legalName) ?> · situação <strong><?= e($company->status) ?></strong> · <?= e($company->describeRegime()) ?>
                <?= $company->isActive() ? '' : ' · não é possível emitir nota para esta empresa.' ?>
            </p>
        <?php endif; ?>
    </div>
    <form method="post" action="<?= e(url('/faturamento')) ?>" class="card-content">
        <?= $view->partial('csrf-field') ?>
        <?php if ($returnTo !== null): ?><input type="hidden" name="return_to" value="<?= e($returnTo) ?>"><?php endif; ?>
        <fieldset class="grid gap-2 sm:grid-cols-2">
            <legend class="label mb-2">A nota sai em nome de</legend>
            <?php foreach (BillingKind::cases() as $kind): ?>
                <label class="flex items-center gap-2 rounded-lg border p-2 text-sm has-[:checked]:border-primary">
                    <input type="radio" name="kind" value="<?= e($kind->value) ?>" class="accent-primary" <?= $kind === $selectedKind ? 'checked' : '' ?> required>
                    <?= e($kind->label()) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <div class="grid gap-3 sm:grid-cols-2">
            <div class="space-y-2">
                <label class="label" for="billing-document">CPF, CNPJ ou documento estrangeiro</label>
                <input class="input" id="billing-document" name="document" maxlength="20" value="<?= e($company?->cnpj ?? $value('document')) ?>" required>
            </div>
            <div class="space-y-2">
                <label class="label" for="billing-name">Nome completo ou razão social</label>
                <input class="input" id="billing-name" name="name" maxlength="150" value="<?= e($company?->legalName ?? $value('name')) ?>" placeholder="Em meu nome: pode deixar vazio">
            </div>
            <div class="space-y-2">
                <label class="label" for="billing-email">E-mail para receber a nota (opcional)</label>
                <input class="input" type="email" id="billing-email" name="email" maxlength="190" value="<?= e($value('email')) ?>">
            </div>
            <div class="space-y-2">
                <label class="label" for="billing-country">País do documento (só estrangeiro)</label>
                <input class="input" id="billing-country" name="country" maxlength="2" pattern="[A-Za-z]{2}" placeholder="ex.: AR, US, PT" value="<?= e($value('country')) ?>">
            </div>
        </div>
        <?= $view->partial('billing-address-fields', ['addressValue' => $addressValue, 'address' => $address]) ?>
        <button type="submit" class="btn btn-primary self-start">Salvar dados</button>
    </form>
</section>
