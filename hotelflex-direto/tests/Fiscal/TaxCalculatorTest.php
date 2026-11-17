<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Fiscal;

use DateTimeImmutable;
use HotelFlex\Fiscal\Domain\HotelFiscalProfile;
use HotelFlex\Fiscal\Domain\InvoiceTaker;
use HotelFlex\Fiscal\Domain\IssWithholdingRule;
use HotelFlex\Fiscal\Domain\LedgerNature;
use HotelFlex\Fiscal\Domain\ServiceType;
use HotelFlex\Fiscal\Domain\TaxCalculator;
use HotelFlex\Fiscal\Domain\TaxDocument;
use HotelFlex\Fiscal\Domain\TaxRegime;
use HotelFlex\Fiscal\Domain\TaxRule;
use HotelFlex\Fiscal\Domain\TaxType;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[TestDox('Cálculo de tributos da nota')]
final class TaxCalculatorTest extends TestCase
{
    #[TestDox('Hospedagem: ISS 5% + CBS 0,9% e IBS 0,1% informativos (ano de teste 2026) sobre R$ 1.350,00')]
    public function testLodgingTaxesIn2026(): void
    {
        $taxes = TaxCalculator::calculate(ServiceType::Lodging, 135000, self::issuer(), false, self::testYearRules(ServiceType::Lodging));

        $this->assertSame([6750, 0, 1215, 135], [$taxes->issCents, $taxes->icmsCents, $taxes->cbsCents, $taxes->ibsCents]);
        $this->assertTrue($taxes->isReformInformative);
        $this->assertFalse($taxes->allowsAcquirerCredit);
        $this->assertSame(8100, $taxes->approximateTotalCents());
    }

    #[TestDox('Café avulso é alimentação: ICMS (18%) no lugar do ISS')]
    public function testFoodPaysIcmsInsteadOfIss(): void
    {
        $taxes = TaxCalculator::calculate(ServiceType::Food, 9000, self::issuer(), false, []);

        $this->assertSame(0, $taxes->issCents);
        $this->assertSame(1620, $taxes->icmsCents);
    }

    #[TestDox('Arredondamento meio para cima, no centavo: 5% de R$ 0,30 = R$ 0,02')]
    public function testRoundsHalfUpToTheCent(): void
    {
        $this->assertSame(2, TaxCalculator::percentOf(30, 500));
        $this->assertSame(1, TaxCalculator::percentOf(29, 500));
    }

    #[TestDox('Redução de 40% (regime específico) diminui a alíquota efetiva: 26,5% → 15,9%')]
    public function testSpecificRegimeReductionLowersTheRate(): void
    {
        $rule = new TaxRule(TaxType::Cbs, ServiceType::Lodging, 2650, 4000, new DateTimeImmutable('2033-01-01'), null, false, false);

        $this->assertSame(1590, $rule->effectiveRateBasisPoints());
    }

    #[TestDox('ISS retido: empresa do mesmo município retém; Simples, outro município ou pessoa física não')]
    public function testIssWithholdingRule(): void
    {
        $rule = IssWithholdingRule::CompanySameCity;
        $company = static fn (?bool $isSimples, string $city) => new InvoiceTaker('Empresa', TaxDocument::cnpj('11222333000181'), null, 'Rua A, 1', $city, $isSimples);

        $this->assertTrue($rule->isWithheldBy($company(false, '3550308'), '3550308'));
        $this->assertFalse($rule->isWithheldBy($company(true, '3550308'), '3550308'));
        $this->assertFalse($rule->isWithheldBy($company(false, '4205407'), '3550308'));
        $this->assertFalse($rule->isWithheldBy(new InvoiceTaker('Ana', TaxDocument::cpf('39053344705'), null, null, '3550308'), '3550308'));
        $this->assertFalse(IssWithholdingRule::Never->isWithheldBy($company(false, '3550308'), '3550308'));
    }

    #[TestDox('No razão, ISS retido fica separado do ISS a recolher e CBS/IBS de 2026 ficam como informativos')]
    public function testLedgerLinesSeparateWhoPays(): void
    {
        $taxes = TaxCalculator::calculate(ServiceType::Lodging, 100000, self::issuer(), true, self::testYearRules(ServiceType::Lodging));
        $natures = array_map(static fn (array $line) => $line['tax']->value . ':' . $line['nature']->value, $taxes->toLedgerLines());

        $this->assertSame(['iss:' . LedgerNature::Withheld->value, 'cbs:informativo', 'ibs:informativo'], $natures);
    }

    private static function issuer(): HotelFiscalProfile
    {
        return new HotelFiscalProfile(1, 'Hotel Teste Ltda', TaxDocument::cnpj('77888999000181'), '123', '110042490114', TaxRegime::Presumed, '3550308', 'Rua B, 2', '09.01', 500, 1800, '1', IssWithholdingRule::CompanySameCity, true);
    }

    /** @return list<TaxRule> */
    private static function testYearRules(ServiceType $type): array
    {
        $from = new DateTimeImmutable('2026-01-01');
        $until = new DateTimeImmutable('2026-12-31');

        return [new TaxRule(TaxType::Cbs, $type, 90, 0, $from, $until, true, false), new TaxRule(TaxType::Ibs, $type, 10, 0, $from, $until, true, false)];
    }
}
