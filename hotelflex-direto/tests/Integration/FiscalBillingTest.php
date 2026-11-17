<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Integration;

use HotelFlex\Domain\DomainException;
use HotelFlex\Fiscal\Service\BillingProfileService;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Objetivo: antes de qualquer pagamento, o sistema sabe em nome de quem sai a nota (o hóspede, outra pessoa,
 * uma empresa ou um estrangeiro) e só aceita dados válidos, do próprio cliente e de empresas regulares.
 */
#[TestDox('Dados para nota fiscal (antes do pagamento)')]
final class FiscalBillingTest extends FiscalTestCase
{
    private const COMPANY = ['kind' => 'empresa', 'document' => '11.222.333/0001-81', 'name' => 'Nome digitado errado', 'email' => 'fiscal@exemplo.test'];

    #[TestDox('Pagar sem escolher em nome de quem sai a nota é recusado (validação no servidor)')]
    public function testPaymentRequiresABillingChoice(): void
    {
        $this->expectExceptionMessage('Escolha em nome de quem sai a nota fiscal.');

        $this->app->fiscal()->billingProfileService()->resolveForPayment($this->user('ana@hotelflex.test'), 1, '');
    }

    #[TestDox('Hóspede não usa os dados de faturamento de outra pessoa, mesmo mandando o id direto')]
    public function testGuestCannotUseSomeoneElsesBillingProfile(): void
    {
        $anaProfile = $this->createBillingProfile('ana@hotelflex.test', ['kind' => 'proprio', 'document' => '390.533.447-05']);

        $this->expectExceptionMessage('Dados para a nota fiscal não encontrados');

        $this->app->fiscal()->billingProfileService()->resolveForPayment($this->user('bruno@hotelflex.test'), 1, (string) $anaProfile);
    }

    #[TestDox('Empresa: razão social e endereço vêm da consulta do CNPJ, não do que foi digitado')]
    public function testCompanyDataComesFromTheCnpjLookup(): void
    {
        $profileId = $this->createBillingProfile('ana@hotelflex.test', self::COMPANY);
        $profile = $this->app->fiscal()->billingProfileService()->listFor($this->user('ana@hotelflex.test'))[0];

        $this->assertSame($profileId, $profile->id);
        $this->assertSame('Empresa Exemplo Viagens Ltda', $profile->name);
        $this->assertSame('3550308', $profile->address?->municipalityCode);
        $this->assertSame('ATIVA', $profile->company?->status);
    }

    #[TestDox('Empresa com CNPJ BAIXADO na Receita não pode ser tomadora da nota')]
    public function testInactiveCompanyIsRejected(): void
    {
        $this->expectExceptionMessage('CNPJ com situação BAIXADA');

        $this->createBillingProfile('ana@hotelflex.test', [...self::COMPANY, 'document' => '11.222.333/0002-62']);
    }

    #[TestDox('CNPJ alfanumérico (formato novo) é aceito como tomador')]
    public function testAlphanumericCnpjCanBeTheTaker(): void
    {
        $this->createBillingProfile('ana@hotelflex.test', [...self::COMPANY, 'document' => '12.ABC.345/01DE-35']);

        $this->assertSame('12ABC34501DE35', $this->app->fiscal()->billingProfileService()->listFor($this->user('ana@hotelflex.test'))[0]->document->value);
    }

    #[TestDox('CPF inválido, e-mail inválido e estrangeiro sem país são recusados')]
    public function testInvalidPersonalDataIsRejected(): void
    {
        $attempts = [
            ['kind' => 'outra-pessoa', 'document' => '390.533.447-06', 'name' => 'Pedro Souza'],
            ['kind' => 'proprio', 'document' => '390.533.447-05', 'email' => 'nao-e-email'],
            ['kind' => 'estrangeiro', 'document' => 'AB123456', 'name' => 'John Smith', 'country' => ''],
        ];
        $errors = 0;
        foreach ($attempts as $input) {
            try {
                $this->createBillingProfile('ana@hotelflex.test', $input);
            } catch (DomainException) {
                $errors++;
            }
        }

        $this->assertSame(3, $errors);
        $this->assertSame(0, $this->countRows('SELECT COUNT(*) FROM billing_profiles'));
    }

    #[TestDox('Hotel que exige CPF/CNPJ recusa "consumidor não identificado"; os outros aceitam')]
    public function testHotelMayRequireAnIdentifiedTaker(): void
    {
        $service = $this->app->fiscal()->billingProfileService();
        $this->assertNull($service->resolveForPayment($this->user('ana@hotelflex.test'), 1, BillingProfileService::ANONYMOUS_CHOICE));
        $this->db->exec('UPDATE hotel_fiscal_profiles SET allows_anonymous_consumer = 0 WHERE hotel_id = 1');

        $this->expectExceptionMessage('Este hotel exige CPF ou CNPJ na nota fiscal.');

        $service->resolveForPayment($this->user('ana@hotelflex.test'), 1, BillingProfileService::ANONYMOUS_CHOICE);
    }

    #[TestDox('Cadastro removido some da lista, mas a nota já emitida guarda a própria cópia dos dados')]
    public function testArchivedProfileDoesNotChangeIssuedInvoices(): void
    {
        $profileId = $this->createBillingProfile('ana@hotelflex.test', ['kind' => 'proprio', 'document' => '390.533.447-05']);
        $reservationId = $this->stayAndCheckOut('ana@hotelflex.test', 1, $profileId);

        $this->app->fiscal()->billingProfileService()->archive($this->user('ana@hotelflex.test'), $profileId);

        $this->assertSame([], $this->app->fiscal()->billingProfileService()->listFor($this->user('ana@hotelflex.test')));
        $this->assertSame('39053344705', $this->invoicesOf($reservationId)[0]->taker->document?->value);
    }
}
