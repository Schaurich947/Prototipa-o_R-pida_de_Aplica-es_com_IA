<?php

declare(strict_types=1);

namespace Comprova\Domain;

final class Money
{
    public const MAX_CENTS = 100_000_000_00;

    /** Parses "1.234,56", "1234,56" or "1234.56" into cents, without floating point. */
    public static function parseToCents(string $input): int
    {
        $normalized = str_replace([' ', 'R$'], '', trim($input));
        if (str_contains($normalized, ',')) {
            $normalized = str_replace(['.', ','], ['', '.'], $normalized);
        }
        if (!preg_match('/^(\d{1,9})(?:\.(\d{1,2}))?$/', $normalized, $matches)) {
            throw new DomainException('Informe um valor válido, por exemplo 1.234,56.');
        }
        $cents = (int) $matches[1] * 100 + (int) str_pad($matches[2] ?? '0', 2, '0');

        return self::assertValidCents($cents);
    }

    public static function assertValidCents(int $cents): int
    {
        if ($cents <= 0 || $cents > self::MAX_CENTS) {
            throw new DomainException('O valor do pagamento deve ser maior que zero.');
        }

        return $cents;
    }

    public static function format(int $cents): string
    {
        return 'R$ ' . number_format($cents / 100, 2, ',', '.');
    }
}
