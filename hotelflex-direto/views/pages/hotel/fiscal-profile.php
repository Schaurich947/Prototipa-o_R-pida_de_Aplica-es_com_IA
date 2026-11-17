<?php

use HotelFlex\Fiscal\Domain\IssWithholdingRule;
use HotelFlex\Fiscal\Domain\TaxRegime;

/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\Hotel $hotel
 * @var list<HotelFlex\Domain\Hotel> $hotelOptions
 * @var HotelFlex\Fiscal\Domain\HotelFiscalProfile|null $profile
 * @var bool $canEdit
 * @var array<string, mixed> $old
 */
$current = [
    'legal_name' => $profile?->legalName, 'cnpj' => $profile?->cnpj->format(), 'municipal_registration' => $profile?->municipalRegistration,
    'state_registration' => $profile?->stateRegistration, 'municipality_code' => $profile?->municipalityCode, 'address' => $profile?->address,
    'service_code' => $profile?->serviceCode ?? '09.01', 'series' => $profile?->series ?? '1',
    'iss_rate' => $profile !== null ? number_format($profile->issRateBasisPoints / 100, 2, ',', '') : '',
    'icms_rate' => $profile !== null ? number_format($profile->icmsRateBasisPoints / 100, 2, ',', '') : '',
];
$value = static fn (string $key): string => (string) ($old[$key] ?? $current[$key] ?? '');
$textFields = [
    ['legal_name', 'Razão social', 'sm:col-span-2'], ['cnpj', 'CNPJ', ''], ['municipal_registration', 'Inscrição municipal', ''],
    ['state_registration', 'Inscrição estadual (para NFC-e do café)', ''], ['municipality_code', 'Código IBGE do município', ''],
    ['address', 'Endereço do estabelecimento', 'sm:col-span-2'], ['service_code', 'Código do serviço (LC 116: 09.01)', ''],
    ['iss_rate', 'Alíquota de ISS (%)', ''], ['icms_rate', 'Alíquota de ICMS no café (%)', ''], ['series', 'Série das notas', ''],
];
$hotelPicker = renderIf($hotelOptions !== [], fn () => $view->partial('select-picker', [
    'action' => '/hotel/fiscal', 'name' => 'hotel', 'label' => 'Hotel', 'options' => toOptions($hotelOptions, static fn ($option) => $option->name), 'selectedId' => $hotel->id,
]));
?>
<?= $view->partial('page-header', [
    'title' => "Dados fiscais · {$hotel->name}",
    'description' => 'O hotel como emissor das notas. Alíquotas e regras devem ser confirmadas com o contador do hotel.',
    'actions' => $hotelPicker,
]) ?>

<?php if ($profile === null): ?>
    <p class="alert alert-error">Dados fiscais não cadastrados: as notas deste hotel ficam rejeitadas até o cadastro.</p>
<?php endif; ?>

<form method="post" action="<?= e(url('/hotel/fiscal')) ?>" class="card">
    <?= $view->partial('csrf-field') ?>
    <input type="hidden" name="hotel_id" value="<?= e($hotel->id) ?>">
    <fieldset class="card-content" <?= $canEdit ? '' : 'disabled' ?>>
        <div class="grid gap-3 sm:grid-cols-3">
            <?php foreach ($textFields as [$name, $label, $span]): ?>
                <div class="space-y-2 <?= e($span) ?>">
                    <label class="label" for="fiscal-<?= e($name) ?>"><?= e($label) ?></label>
                    <input class="input" id="fiscal-<?= e($name) ?>" name="<?= e($name) ?>" maxlength="255" value="<?= e($value($name)) ?>">
                </div>
            <?php endforeach; ?>
            <div class="space-y-2">
                <label class="label" for="fiscal-regime">Regime tributário</label>
                <select class="input" id="fiscal-regime" name="tax_regime" required>
                    <?php foreach (TaxRegime::cases() as $regime): ?>
                        <option value="<?= e($regime->value) ?>" <?= ($old['tax_regime'] ?? $profile?->taxRegime->value) === $regime->value ? 'selected' : '' ?>><?= e($regime->label()) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-2 sm:col-span-2">
                <label class="label" for="fiscal-withholding">Retenção do ISS</label>
                <select class="input" id="fiscal-withholding" name="iss_withholding" required>
                    <?php foreach (IssWithholdingRule::cases() as $rule): ?>
                        <option value="<?= e($rule->value) ?>" <?= ($old['iss_withholding'] ?? $profile?->issWithholding->value) === $rule->value ? 'selected' : '' ?>><?= e($rule->label()) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="allows_anonymous_consumer" value="1" class="accent-primary" <?= ($profile?->allowsAnonymousConsumer ?? true) ? 'checked' : '' ?>>
            Aceitar nota para "consumidor não identificado" (pessoa física sem CPF)
        </label>
    </fieldset>
    <footer class="card-footer justify-between">
        <p class="text-xs text-muted-foreground">Certificado digital: fica fora do sistema nesta fase (emissão simulada).</p>
        <?php if ($canEdit): ?>
            <button type="submit" class="btn btn-primary">Salvar dados fiscais</button>
        <?php else: ?>
            <span class="text-xs text-muted-foreground">Somente o administrador altera estes dados.</span>
        <?php endif; ?>
    </footer>
</form>
