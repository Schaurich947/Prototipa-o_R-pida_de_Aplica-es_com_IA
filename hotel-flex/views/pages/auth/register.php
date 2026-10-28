<?php

use HotelFlex\Service\AuthService;

/**
 * @var HotelFlex\View\View $view
 * @var array<string, mixed> $old
 */
$fields = [
    ['id' => 'register-name', 'name' => 'name', 'label' => 'Nome completo', 'type' => 'text', 'autocomplete' => 'name', 'extra' => 'minlength="2" maxlength="120"'],
    ['id' => 'register-email', 'name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'autocomplete' => 'email', 'extra' => 'maxlength="190"'],
    ['id' => 'register-password', 'name' => 'password', 'label' => 'Senha (mín. ' . AuthService::MIN_PASSWORD_LENGTH . ' caracteres)', 'type' => 'password', 'autocomplete' => 'new-password', 'extra' => 'minlength="' . AuthService::MIN_PASSWORD_LENGTH . '"'],
    ['id' => 'register-confirmation', 'name' => 'password_confirmation', 'label' => 'Confirme a senha', 'type' => 'password', 'autocomplete' => 'new-password', 'extra' => 'minlength="' . AuthService::MIN_PASSWORD_LENGTH . '"'],
];
?>
<div class="mx-auto w-full max-w-sm space-y-6">
    <?= $view->partial('auth-brand', ['subtitle' => 'Crie sua conta para reservar e gerenciar suas estadias.']) ?>
    <form method="post" action="<?= e(url('/cadastro')) ?>" class="card">
        <div class="card-content">
            <?= $view->partial('csrf-field') ?>
            <?php foreach ($fields as $field): ?>
                <div class="space-y-2">
                    <label class="label" for="<?= e($field['id']) ?>"><?= e($field['label']) ?></label>
                    <input class="select sm:w-full" type="<?= e($field['type']) ?>" id="<?= e($field['id']) ?>" name="<?= e($field['name']) ?>"
                        autocomplete="<?= e($field['autocomplete']) ?>" value="<?= $field['type'] === 'password' ? '' : e($old[$field['name']] ?? '') ?>" <?= $field['extra'] ?> required>
                </div>
            <?php endforeach; ?>
        </div>
        <footer class="card-footer flex-col">
            <button type="submit" class="btn btn-primary btn-block">Criar conta</button>
            <p class="text-sm text-muted-foreground">Já tem conta? <a class="font-medium text-foreground underline" href="<?= e(url('/login')) ?>">Entrar</a></p>
        </footer>
    </form>
</div>
