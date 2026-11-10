<?php
/**
 * Phone + consent, shared by sign-up and profile.
 *
 * @var string $phoneValue
 * @var bool $isConsentChecked
 */
?>
<div class="space-y-2">
    <label class="label" for="whatsapp-phone">Celular com WhatsApp (opcional)</label>
    <input class="input" type="tel" inputmode="tel" id="whatsapp-phone" name="whatsapp_phone" placeholder="(11) 98765-4321" value="<?= e($phoneValue) ?>">
</div>
<label class="flex items-start gap-2 rounded-lg border p-3 text-sm">
    <input type="checkbox" name="whatsapp_consent" value="1" class="mt-0.5 accent-primary" <?= $isConsentChecked ? 'checked' : '' ?>>
    <span>Quero receber pelo WhatsApp as atualizações das minhas reservas (pagamento, confirmação, horários). <span class="text-muted-foreground">Você pode desligar quando quiser em Meu perfil.</span></span>
</label>
