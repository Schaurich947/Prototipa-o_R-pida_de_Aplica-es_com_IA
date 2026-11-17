<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Integration;

use HotelFlex\Domain\DomainException;
use HotelFlex\Fiscal\Domain\BillingProfile;
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
        $this->expectExceptionMessage('Informe o CPF ou CNPJ para a nota fiscal: o hotel é obrigado a emiti-la.');

        $this->app->fiscal()->billingProfileService()->resolveForPayment($this->user('ana@hotelflex.test'), '');
    }

    #[TestDox('Hóspede não usa os dados de faturamento de outra pessoa, mesmo mandando o id direto')]
    public function testGuestCannotUseSomeoneElsesBillingProfile(): void
    {
        $anaProfile = $this->createBillingProfile('ana@hotelflex.test', ['kind' => 'proprio', 'document' => '390.533.447-05']);

        $this->expectExceptionMessage('Dados para a nota fiscal não encontrados');

        $this->app->fiscal()->billingProfileService()->resolveForPayment($this->user('bruno@hotelflex.test'), (string) $anaProfile);
    }

    #[TestDox('Empresa: razão social e endereço vêm da consulta do CNPJ, não do que foi digitado')]
    public function testCompanyDataComesFromTheCnpjLookup(): void
    {
        $profile = $this->findProfile('ana@hotelflex.test', $this->createBillingProfile('ana@hotelflex.test', self::COMPANY));

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
        $profileId = $this->createBillingProfile('ana@hotelflex.test', [...self::COMPANY, 'document' => '12.ABC.345/01DE-35']);

        $this->assertSame('12ABC34501DE35', $this->findProfile('ana@hotelflex.test', $profileId)?->document->value);
    }

    #[TestDox('CPF inválido, e-mail inválido e estrangeiro sem país são recusados')]
    public function testInvalidPersonalDataIsRejected(): void
    {
        $attempts = [
            ['kind' => 'outra-pessoa', 'document' => '390.533.447-06', 'name' => 'Pedro Souza'],
            ['kind' => 'proprio', 'document' => '390.533.447-05', 'email' => 'nao-e-email'],
            ['kind' => 'estrangeiro', 'document' => 'AB123456', 'name' => 'John Smith', 'country' => ''],
        ];
        $profilesBefore = $this->countRows('SELECT COUNT(*) FROM billing_profiles');
        $errors = 0;
        foreach ($attempts as $input) {
            try {
                $this->createBillingProfile('ana@hotelflex.test', $input);
            } catch (DomainException) {
                $errors++;
            }
        }

        $this->assertSame(3, $errors);
        $this->assertSame($profilesBefore, $this->countRows('SELECT COUNT(*) FROM billing_profiles'));
    }

    #[TestDox('Não existe reserva sem CPF/CNPJ: a antiga opção "consumidor" é recusada no servidor')]
    public function testThereIsNoAnonymousOption(): void
    {
        $this->expectExceptionMessage('Dados para a nota fiscal não encontrados');

        $this->app->fiscal()->billingProfileService()->resolveForPayment($this->user('ana@hotelflex.test'), 'consumidor');
    }

    #[TestDox('Inscrição estadual só para empresa: CPF com IE é recusado; IE da empresa (ou ISENTO) vai para a nota')]
    public function testStateRegistrationOnlyForCompanies(): void
    {
        try {
            $this->createBillingProfile('ana@hotelflex.test', ['kind' => 'proprio', 'document' => '390.533.447-05', 'state_registration' => '110042490114']);
            $this->fail('Pessoa física não tem inscrição estadual');
        } catch (DomainException $error) {
            $this->assertStringContainsString('só para empresa', $error->getMessage());
        }
        $companyId = $this->createBillingProfile('ana@hotelflex.test', [...self::COMPANY, 'state_registration' => '110.042.490.114']);
        $reservationId = $this->stayAndCheckOut('ana@hotelflex.test', 1, $companyId);

        $this->assertSame('110042490114', $this->invoicesOf($reservationId)[0]->taker->stateRegistration);
    }

    #[TestDox('Cadastro removido some da lista, mas a nota já emitida guarda a própria cópia dos dados')]
    public function testArchivedProfileDoesNotChangeIssuedInvoices(): void
    {
        $profileId = $this->createBillingProfile('ana@hotelflex.test', ['kind' => 'proprio', 'document' => '390.533.447-05']);
        $reservationId = $this->stayAndCheckOut('ana@hotelflex.test', 1, $profileId);

        $this->app->fiscal()->billingProfileService()->archive($this->user('ana@hotelflex.test'), $profileId);

        $this->assertNull($this->findProfile('ana@hotelflex.test', $profileId));
        $this->assertSame('39053344705', $this->invoicesOf($reservationId)[0]->taker->document?->value);
    }

    private function findProfile(string $email, int $profileId): ?BillingProfile
    {
        foreach ($this->app->fiscal()->billingProfileService()->listFor($this->user($email)) as $profile) {
            if ($profile->id === $profileId) {
                return $profile;
            }
        }

        return null;
    }
}
