<?php
/**
 * Month's taxes by nature: what the hotel pays, what takers withheld, and the CBS/IBS shown for information in 2026.
 *
 * @var list<array{tax: HotelFlex\Fiscal\Domain\TaxType, nature: HotelFlex\Fiscal\Domain\LedgerNature, cents: int}> $lines
 */
?>
<section class="card">
    <header class="card-header">
        <div>
            <h2 class="card-title">Pré-controle de tributos do mês</h2>
            <p class="card-description">Soma das notas autorizadas menos os estornos das canceladas. Confira sempre com o contador.</p>
        </div>
    </header>
    <div class="card-content">
        <?php if ($lines === []): ?>
            <p class="text-muted-foreground">Nenhum tributo lançado neste mês.</p>
        <?php else: ?>
            <dl class="grid gap-3 sm:grid-cols-3">
                <?php foreach ($lines as $line): ?>
                    <div class="rounded-lg border p-3">
                        <dt class="text-xs text-muted-foreground"><?= e($line['tax']->label()) ?> · <?= e($line['nature']->label()) ?></dt>
                        <dd class="text-lg font-semibold tabular-nums"><?= e(formatMoney($line['cents'])) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
        <p class="text-xs text-muted-foreground">
            2026 é o ano de teste da reforma: CBS (0,9%) e IBS (0,1%) aparecem na nota, sem recolhimento. Na hospedagem e na alimentação, a empresa tomadora não aproveita crédito de IBS/CBS (regimes específicos da LC 214/2025).
        </p>
    </div>
</section>
