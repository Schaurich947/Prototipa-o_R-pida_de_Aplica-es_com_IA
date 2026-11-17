<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

/** Check digits (módulo 11) of CPF and CNPJ, including the alphanumeric CNPJ (IN RFB 2.229/2024). */
final class TaxDocumentValidator
{
    private const CNPJ_FIRST_WEIGHTS = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    private const CNPJ_SECOND_WEIGHTS = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    public static function isValidCpf(string $digits): bool
    {
        if (!preg_match('/^\d{11}$/', $digits) || self::hasAllSameCharacters($digits)) {
            return false;
        }
        $values = array_map('intval', str_split($digits));
        $first = self::calculateDigit(array_slice($values, 0, 9), range(10, 2));
        $second = self::calculateDigit([...array_slice($values, 0, 9), $first], range(11, 2));

        return $values[9] === $first && $values[10] === $second;
    }

    /** The first 12 characters may be letters; each one counts as its ASCII code minus 48. The two check digits are numbers. */
    public static function isValidCnpj(string $value): bool
    {
        if (!preg_match('/^[0-9A-Z]{12}\d{2}$/', $value) || self::hasAllSameCharacters($value)) {
            return false;
        }
        $values = array_map(static fn (string $character) => ord($character) - 48, str_split(substr($value, 0, 12)));
        $first = self::calculateDigit($values, self::CNPJ_FIRST_WEIGHTS);
        $second = self::calculateDigit([...$values, $first], self::CNPJ_SECOND_WEIGHTS);

        return substr($value, 12) === "{$first}{$second}";
    }

    /**
     * @param list<int> $values
     * @param list<int> $weights
     */
    private static function calculateDigit(array $values, array $weights): int
    {
        $sum = 0;
        foreach ($values as $index => $value) {
            $sum += $value * $weights[$index];
        }
        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }

    private static function hasAllSameCharacters(string $value): bool
    {
        return count(array_unique(str_split($value))) === 1;
    }
}
