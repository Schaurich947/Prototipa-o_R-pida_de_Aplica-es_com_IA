<?php /** @var list<array{type: string, message: string}> $flashes */ ?>
<?php foreach ($flashes as $flash): ?>
    <p class="alert <?= $flash['type'] === 'error' ? 'alert-error' : 'alert-success' ?>" role="status"><?= e($flash['message']) ?></p>
<?php endforeach; ?>
