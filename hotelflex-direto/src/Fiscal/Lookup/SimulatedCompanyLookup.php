<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Lookup;

use HotelFlex\Domain\Clock;
use HotelFlex\Fiscal\Domain\BillingAddress;
use HotelFlex\Fiscal\Domain\CompanyRecord;
use HotelFlex\Fiscal\Domain\TaxDocument;

/** Offline lookup for the simulator and tests. Fictitious companies; any other valid CNPJ is an active company in São Paulo. */
final class SimulatedCompanyLookup implements CompanyLookup
{
    public const SOURCE = 'simulacao';

    /** @var array<string, array{0: string, 1: string, 2: ?bool, 3: string, 4: string, 5: string}> name, status, Simples, city, state, IBGE code */
    private const COMPANIES = [
        '11222333000181' => ['Empresa Exemplo Viagens Ltda', 'ATIVA', false, 'São Paulo', 'SP', '3550308'],
        '11222333000262' => ['Empresa Exemplo Viagens Ltda (filial)', 'BAIXADA', false, 'São Paulo', 'SP', '3550308'],
        '44555666000181' => ['Padaria Bom Dia Ltda', 'ATIVA', true, 'São Paulo', 'SP', '3550308'],
        '12ABC34501DE35' => ['Alfa Numérica Tecnologia S.A.', 'ATIVA', false, 'Florianópolis', 'SC', '4205407'],
    ];

    public function __construct(private readonly Clock $clock)
    {
    }

    public function find(TaxDocument $cnpj): ?CompanyRecord
    {
        [$name, $status, $isSimples, $city, $state, $municipalityCode] = self::COMPANIES[$cnpj->value]
            ?? ["Empresa Simulada {$cnpj->format()}", 'ATIVA', null, 'São Paulo', 'SP', '3550308'];
        $address = new BillingAddress('Rua Exemplo', '100', 'Centro', $city, $state, $state === 'SP' ? '01001000' : '88010000', $municipalityCode);

        return new CompanyRecord($cnpj->value, $name, $status, $isSimples, false, $address, self::SOURCE, $this->clock->now());
    }
}
