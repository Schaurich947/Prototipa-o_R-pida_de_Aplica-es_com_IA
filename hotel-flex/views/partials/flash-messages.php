<?php /** @var list<array{type: string, message: string}> $flashes */ ?>
<?php if (!empty($flashes)): ?>
    <div class="space-y-2" role="status" aria-live="polite">
        <?php foreach ($flashes as $flash): ?>
            <p class="alert <?= $flash['type'] === 'error' ? 'alert-error' : 'alert-success' ?>"><?= e($flash['message']) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
