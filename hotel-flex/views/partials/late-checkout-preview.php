<?php
/**
 * Server-rendered first state; late-checkout.js keeps it in sync while the slider moves.
 *
 * @var int $hours
 * @var int $hourlyRateCents
 */
?>
<p class="text-sm" aria-live="polite" data-preview>
    <?php if ($hours === 0): ?>
        <span class="text-muted-foreground">Saída padrão às <?= e(formatCheckoutHour(0)) ?></span>
    <?php else: ?>
        <span class="font-medium">+<?= e(formatHours($hours)) ?></span> =
        <span class="font-semibold text-success">+<?= e(formatMoney($hours * $hourlyRateCents)) ?></span>
        <span class="text-muted-foreground">· saída às <?= e(formatCheckoutHour($hours)) ?></span>
    <?php endif; ?>
</p>
