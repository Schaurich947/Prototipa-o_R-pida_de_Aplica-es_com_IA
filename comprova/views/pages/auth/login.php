<?php
/**
 * @var Comprova\View\View $view
 * @var array<string, mixed> $old
 */
?>
<?= $view->partial('brand') ?>
<form method="post" action="<?= e(url('/login')) ?>" class="panel panel-body">
    <?= $view->partial('csrf-field') ?>
    <h1>Entrar</h1>
    <p class="subtitle">Envio automático de comprovantes por e-mail e WhatsApp.</p>
    <div class="form-grid form-grid-spaced">
        <div class="field full">
            <label for="login-email">E-mail</label>
            <input type="email" id="login-email" name="email" autocomplete="email" value="<?= e($old['email'] ?? '') ?>" required autofocus>
        </div>
        <div class="field full">
            <label for="login-password">Senha</label>
            <input type="password" id="login-password" name="password" autocomplete="current-password" required>
        </div>
    </div>
    <div class="form-actions"><button type="submit" class="primary-button">Entrar</button></div>
</form>
<p class="hint">Demonstração: <code>admin@comprova.test</code> · senha <code>Demo@1234</code></p>
