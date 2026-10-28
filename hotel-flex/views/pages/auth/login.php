<?php
/**
 * @var HotelFlex\View\View $view
 * @var array<string, mixed> $old
 */
$demoAccounts = [
    'Administrador da plataforma' => 'admin@hotelflex.test',
    'Recepção · HotelFlex Centro' => 'recepcao.centro@hotelflex.test',
    'Recepção · Pousada Mar Azul' => 'recepcao.marazul@hotelflex.test',
    'Hóspede · Ana Souza' => 'ana@hotelflex.test',
];
?>
<div class="mx-auto w-full max-w-sm space-y-6">
    <?= $view->partial('auth-brand', ['subtitle' => 'Entrada antecipada e saída tardia por hora, em vários hotéis.']) ?>
    <form method="post" action="<?= e(url('/login')) ?>" class="card">
        <div class="card-content">
            <?= $view->partial('csrf-field') ?>
            <div class="space-y-2">
                <label class="label" for="login-email">E-mail</label>
                <input class="select sm:w-full" type="email" id="login-email" name="email" autocomplete="email" value="<?= e($old['email'] ?? '') ?>" required autofocus>
            </div>
            <div class="space-y-2">
                <label class="label" for="login-password">Senha</label>
                <input class="select sm:w-full" type="password" id="login-password" name="password" autocomplete="current-password" required>
            </div>
        </div>
        <footer class="card-footer flex-col">
            <button type="submit" class="btn btn-primary btn-block">Entrar</button>
            <p class="text-sm text-muted-foreground">Novo por aqui? <a class="font-medium text-foreground underline" href="<?= e(url('/cadastro')) ?>">Criar conta de hóspede</a></p>
        </footer>
    </form>
    <details class="rounded-lg border p-3 text-sm">
        <summary class="cursor-pointer font-medium">Contas de demonstração (senha Demo@1234)</summary>
        <ul class="mt-2 space-y-1 text-muted-foreground">
            <?php foreach ($demoAccounts as $label => $email): ?>
                <li><?= e($label) ?>: <code><?= e($email) ?></code></li>
            <?php endforeach; ?>
        </ul>
    </details>
</div>
