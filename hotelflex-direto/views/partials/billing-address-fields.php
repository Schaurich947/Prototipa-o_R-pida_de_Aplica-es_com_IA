<?php
/**
 * Address on the invoice: required for companies, optional for people.
 *
 * @var callable(string, ?string): string $addressValue typed value, or the one from the CNPJ lookup
 * @var HotelFlex\Fiscal\Domain\BillingAddress|null $address
 */
$fields = [
    ['street', 'Rua / avenida', $address?->street, 'sm:col-span-2', 120],
    ['number', 'Número', $address?->number, '', 20],
    ['district', 'Bairro', $address?->district, '', 120],
    ['city', 'Cidade', $address?->city, '', 120],
    ['state', 'UF', $address?->state, '', 2],
    ['postal_code', 'CEP', $address?->postalCode, '', 9],
    ['municipality_code', 'Código IBGE da cidade (opcional)', $address?->municipalityCode, '', 7],
];
?>
<fieldset class="grid gap-3 sm:grid-cols-3">
    <legend class="label mb-2">Endereço (obrigatório para empresa)</legend>
    <?php foreach ($fields as [$name, $label, $fromCompany, $span, $maxLength]): ?>
        <div class="space-y-2 <?= e($span) ?>">
            <label class="label" for="billing-<?= e($name) ?>"><?= e($label) ?></label>
            <input class="input" id="billing-<?= e($name) ?>" name="<?= e($name) ?>" maxlength="<?= e($maxLength) ?>" value="<?= e($addressValue($name, $fromCompany)) ?>">
        </div>
    <?php endforeach; ?>
</fieldset>
