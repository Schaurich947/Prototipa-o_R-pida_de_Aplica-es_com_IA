<?php
/**
 * @var HotelFlex\View\View $view
 * @var list<HotelFlex\Domain\Reservation> $reservations
 */
$profiles = [
    ['role' => 'staff', 'title' => 'Recepção / Governança', 'description' => 'Liberar quartos, ajustar horas e responder solicitações.'],
    ['role' => 'guest', 'title' => 'Hóspede', 'description' => 'Consultar a reserva e pedir horários flexíveis.'],
    ['role' => 'admin', 'title' => 'Administrador', 'description' => 'Acesso completo às telas do hotel e do hóspede.'],
];
?>
<div class="space-y-2 text-center">
    <div class="flex justify-center"><?= $view->partial('icon-bed') ?></div>
    <h1 class="text-2xl font-semibold tracking-tight">HotelFlex</h1>
    <p class="text-sm text-muted-foreground">Entrada antecipada e late check-out por hora. Escolha um perfil.</p>
</div>
<div class="grid gap-4 md:grid-cols-3">
    <?php foreach ($profiles as $profile): ?>
        <form method="post" action="<?= e(url('/login')) ?>" class="card">
            <?= $view->partial('csrf-field') ?>
            <input type="hidden" name="role" value="<?= e($profile['role']) ?>">
            <header class="card-header">
                <div>
                    <h2 class="card-title"><?= e($profile['title']) ?></h2>
                    <p class="card-description"><?= e($profile['description']) ?></p>
                </div>
            </header>
            <div class="card-content">
                <?php if ($profile['role'] === 'guest'): ?>
                    <label class="label" for="guest-reservation">Sua reserva</label>
                    <select class="select sm:w-full" id="guest-reservation" name="reservation_id" required>
                        <option value="">Selecione a reserva</option>
                        <?= $view->partial('reservation-options', ['reservations' => $reservations, 'selectedId' => null]) ?>
                    </select>
                <?php endif; ?>
            </div>
            <footer class="card-footer mt-auto">
                <button type="submit" class="btn btn-primary btn-block">Entrar</button>
            </footer>
        </form>
    <?php endforeach; ?>
</div>
<p class="text-center text-xs text-muted-foreground">Acesso de demonstração. Em produção, cada perfil exige autenticação real.</p>
