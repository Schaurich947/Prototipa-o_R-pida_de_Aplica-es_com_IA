<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

/** Pure tax math, rounded half up to the cent. */
final class TaxCalculator
{
    /** @param list<TaxRule> $reformRules CBS/IBS rules valid on the invoice date for this service type */
    public static function calculate(ServiceType $serviceType, int $amountCents, HotelFiscalProfile $issuer, bool $isIssWithheld, array $reformRules): TaxBreakdown
    {
        $isLodging = $serviceType === ServiceType::Lodging;
        $reform = self::calculateReformTaxes($amountCents, $reformRules);

        return new TaxBreakdown(
            $isLodging ? self::percentOf($amountCents, $issuer->issRateBasisPoints) : 0,
            $isLodging && $isIssWithheld,
            $isLodging ? 0 : self::percentOf($amountCents, $issuer->icmsRateBasisPoints),
            $reform[TaxType::Cbs->value],
            $reform[TaxType::Ibs->value],
            $reform['informative'],
            $reform['credit'],
        );
    }

    /** 10000 basis points = 100%. */
    public static function percentOf(int $cents, int $basisPoints): int
    {
        return intdiv($cents * $basisPoints + 5000, 10000);
    }

    /**
     * @param list<TaxRule> $rules
     * @return array{cbs: int, ibs: int, informative: bool, credit: bool}
     */
    private static function calculateReformTaxes(int $amountCents, array $rules): array
    {
        $result = [TaxType::Cbs->value => 0, TaxType::Ibs->value => 0, 'informative' => true, 'credit' => false];
        foreach ($rules as $rule) {
            $result[$rule->tax->value] += self::percentOf($amountCents, $rule->effectiveRateBasisPoints());
            $result['informative'] = $result['informative'] && $rule->isInformative;
            $result['credit'] = $result['credit'] || $rule->allowsAcquirerCredit;
        }

        return $result;
    }
}
