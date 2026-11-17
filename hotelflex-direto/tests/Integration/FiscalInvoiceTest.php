<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Integration;

use HotelFlex\Domain\PaymentMethod;
use HotelFlex\Domain\RequestType;
use HotelFlex\Fiscal\Issuer\InvoiceXmlBuilder;
use HotelFlex\Fiscal\Issuer\SimulatedInvoiceIssuer;
use HotelFlex\Tests\Support\FlakyInvoiceIssuer;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Objetivo: cada serviço prestado gera exatamente uma nota, com o valor final, o tomador certo, numeração sem
 * buracos nem repetição, e tributos lançados no pré-controle, mesmo quando o emissor falha.
 */
#[TestDox('Emissão de notas fiscais (simulada)')]
final class FiscalInvoiceTest extends FiscalTestCase
{
    #[TestDox('Nenhuma nota antes do check-out; no check-out entra uma na fila com o valor final (diária + 2h extras)')]
    public function testInvoiceIsQueuedAtCheckOutWithTheFinalAmount(): void
    {
        $staff = $this->user('recepcao.centro@hotelflex.test');
        $reservationId = $this->app->reservationService()->book($this->user('ana@hotelflex.test'), 1, self::dates('2026-12-01', '2026-12-02'), \HotelFlex\Domain\PaymentPlan::Full, true);
        $this->payFirstCharge($reservationId);
        $this->app->roomService()->checkIn($staff, $reservationId);
        $this->app->roomService()->setFlexibleHours($staff, $reservationId, RequestType::LateCheckout, 2);
        $this->app->chargeService()->receiveAtDesk($staff, $this->openCharges($reservationId)[0]['id'], PaymentMethod::Cash);
        $this->assertSame([], $this->invoicesOf($reservationId), 'Pagamento antecipado não gera nota');

        $this->app->roomService()->checkOut($staff, $reservationId);

        $invoices = $this->invoicesOf($reservationId);
        $this->assertCount(1, $invoices);
        $this->assertSame(30400 + 10000, $invoices[0]->amountCents, 'R$ 304,00 da diária com 5% de desconto + R$ 100,00 de saída tardia');
        $this->assertSame('pending', $invoices[0]->status->value);
    }

    #[TestDox('Emissão: numeração sequencial (1, 2) e ISS 5% + CBS 0,9% + IBS 0,1% lançados no pré-controle')]
    public function testIssueNumbersInvoicesAndRecordsTaxes(): void
    {
        $first = $this->stayAndCheckOut('ana@hotelflex.test', 1);
        $second = $this->stayAndCheckOut('bruno@hotelflex.test', 2);

        $this->assertSame(['authorized' => 2, 'rejected' => 0, 'retrying' => 0], $this->processQueue());

        $this->assertSame([1, 2], [$this->invoicesOf($first)[0]->number, $this->invoicesOf($second)[0]->number]);
        $this->assertSame(['cbs:informativo' => 548, 'ibs:informativo' => 60, 'iss:proprio' => 3040], $this->ledgerTotals());
        $this->assertStringStartsWith('SIMULACAO', (string) $this->invoicesOf($first)[0]->accessKey);
    }

    #[TestDox('Nota em nome da empresa escolhida (outro CNPJ), com o nome do hóspede na descrição; empresa de SP retém o ISS')]
    public function testCompanyTakerWithholdsIss(): void
    {
        $companyId = $this->createBillingProfile('ana@hotelflex.test', ['kind' => 'empresa', 'document' => '11222333000181']);
        $reservationId = $this->stayAndCheckOut('ana@hotelflex.test', 1, $companyId);
        $this->processQueue();

        $invoice = $this->invoicesOf($reservationId)[0];
        $this->assertSame('Empresa Exemplo Viagens Ltda', $invoice->taker->name);
        $this->assertStringContainsString('Hospedagem de Ana Souza', $invoice->description);
        $this->assertTrue($invoice->taxes->isIssWithheld);
        $this->assertSame(['cbs:informativo' => 274, 'ibs:informativo' => 30, 'iss:retido' => 1520], $this->ledgerTotals());
    }

    #[TestDox('Empresa do Simples Nacional não retém o ISS: o hotel recolhe')]
    public function testSimplesCompanyDoesNotWithhold(): void
    {
        $companyId = $this->createBillingProfile('ana@hotelflex.test', ['kind' => 'empresa', 'document' => '44555666000181']);
        $this->processQueueAfter($this->stayAndCheckOut('ana@hotelflex.test', 1, $companyId));

        $this->assertSame(1520, $this->ledgerTotals()['iss:proprio']);
    }

    #[TestDox('Emissor fora do ar 2 vezes: a nota volta para a fila com o mesmo número e é autorizada depois')]
    public function testOutageRetriesWithoutSkippingNumbers(): void
    {
        $flaky = new FlakyInvoiceIssuer($this->createSimulatedIssuer(), outagesLeft: 2);
        $this->app->fiscal()->useIssuer($flaky);
        $reservationId = $this->stayAndCheckOut('ana@hotelflex.test', 1);

        $this->assertSame(1, $this->processQueue()['retrying']);
        $this->clock->travelTo('2026-12-02 11:05:00');
        $this->assertSame(1, $this->processQueue()['retrying']);
        $this->clock->travelTo('2026-12-02 11:30:00');
        $this->assertSame(1, $this->processQueue()['authorized']);

        $this->assertSame(1, $this->invoicesOf($reservationId)[0]->number);
        $this->assertSame(2, $this->countRows('SELECT next_number FROM hotel_fiscal_profiles WHERE hotel_id = 1'), 'Nenhum número gasto à toa');
    }

    #[TestDox('Resposta perdida (o emissor autorizou, mas a conexão caiu): a nova tentativa consulta antes e não emite em dobro')]
    public function testLostResponseIsResolvedByQueryingFirst(): void
    {
        $flaky = new FlakyInvoiceIssuer($this->createSimulatedIssuer(), lostResponsesLeft: 1);
        $this->app->fiscal()->useIssuer($flaky);
        $reservationId = $this->stayAndCheckOut('ana@hotelflex.test', 1);

        $this->processQueue();
        $this->clock->travelTo('2026-12-02 11:30:00');
        $this->processQueue();

        $this->assertSame('authorized', $this->invoicesOf($reservationId)[0]->status->value);
        $this->assertSame(1, $flaky->issueCalls, 'A segunda tentativa encontrou a nota pela consulta e não emitiu de novo');
        $this->assertCount(1, glob($this->storageRoot . '/storage/fiscal/*.xml'));
        $this->assertSame(1520, $this->ledgerTotals()['iss:proprio'], 'Tributo lançado uma vez só');
    }

    #[TestDox('Cancelamento exige motivo de 15+ caracteres e estorna os tributos no pré-controle')]
    public function testCancellationNeedsAReasonAndReversesTaxes(): void
    {
        $invoiceId = $this->processQueueAfter($this->stayAndCheckOut('ana@hotelflex.test', 1));
        $staff = $this->user('recepcao.centro@hotelflex.test');
        try {
            $this->app->fiscal()->invoiceService()->cancel($staff, $invoiceId, 'erro');
            $this->fail('Motivo curto deveria ser recusado');
        } catch (\HotelFlex\Domain\DomainException $error) {
            $this->assertStringContainsString('mínimo de 15', $error->getMessage());
        }

        $this->app->fiscal()->invoiceService()->cancel($staff, $invoiceId, 'Tomador informado errado pelo hóspede');

        $this->assertSame(['cbs:informativo' => 0, 'ibs:informativo' => 0, 'iss:proprio' => 0], $this->ledgerTotals());
        $this->assertSame(6, $this->countRows('SELECT COUNT(*) FROM tax_ledger'), 'Lançamento e estorno ficam no histórico');
    }

    #[TestDox('Hotel sem dados fiscais: nota rejeitada com o motivo; depois do cadastro, "reenviar" autoriza')]
    public function testHotelWithoutFiscalDataIsRejectedUntilFixed(): void
    {
        $reservationId = $this->stayAndCheckOut('ana@hotelflex.test', 4, null, 'recepcao.marazul@hotelflex.test');
        $this->assertSame(1, $this->processQueue()['rejected']);
        $invoice = $this->invoicesOf($reservationId)[0];
        $this->assertStringContainsString('Cadastre os dados fiscais', (string) $invoice->lastError);

        $this->app->fiscal()->hotelFiscalService()->save($this->user('admin@hotelflex.test'), 2, [
            'legal_name' => 'Pousada Mar Azul Ltda', 'cnpj' => '98.765.000/0001-87', 'municipal_registration' => '7654321', 'tax_regime' => 'simples',
            'municipality_code' => '4205407', 'address' => 'Av. Beira-Mar, 2000', 'service_code' => '09.01', 'iss_rate' => '3,00', 'series' => '1', 'iss_withholding' => 'nunca',
        ]);
        $this->app->fiscal()->invoiceService()->requeue($this->user('recepcao.marazul@hotelflex.test'), $invoice->id);

        $this->assertSame(1, $this->processQueue()['authorized']);
        $this->assertSame(1083, $this->invoicesOf($reservationId)[0]->taxes->issCents, 'ISS 3% de R$ 361,00');
    }

    #[TestDox('Café servido gera NFC-e com ICMS; venda na hora com "CPF na nota" sai com o CPF')]
    public function testBreakfastIssuesConsumerInvoices(): void
    {
        $breakfast = $this->app->breakfastService();
        $staff = $this->user('recepcao.centro@hotelflex.test');
        $bookingId = $breakfast->book($this->user('carla@hotelflex.test'), 1, new \DateTimeImmutable('2026-12-02'), 2, \HotelFlex\Domain\BreakfastPaymentOption::Desk);
        $breakfast->receiveAtDesk($staff, $bookingId, PaymentMethod::Cash);
        $this->clock->travelTo('2026-12-02 08:00:00');
        $breakfast->markServed($staff, $bookingId);
        $breakfast->sellWalkIn($staff, 1, 1, PaymentMethod::Cash, 'Pedro', '390.533.447-05');

        $this->assertSame(['authorized' => 2, 'rejected' => 0, 'retrying' => 0], $this->processQueue());
        $this->assertSame(['cbs:informativo' => 81 + 50, 'ibs:informativo' => 9 + 6, 'icms:proprio' => 1620 + 990], $this->ledgerTotals(), 'ICMS 18% de R$ 90,00 (2 × R$ 45) e de R$ 55,00 (venda na hora)');
        $this->assertSame(1, $this->countRows("SELECT COUNT(*) FROM invoices WHERE kind = 'nfce' AND taker_document = '39053344705' AND taker_name = 'Pedro'"));
    }

    #[TestDox('Hóspede não vê a nota de outro hóspede; recepção de outro hotel não cancela; o XML diz que é simulação')]
    public function testInvoiceAccessIsRestricted(): void
    {
        $invoiceId = $this->processQueueAfter($this->stayAndCheckOut('ana@hotelflex.test', 1));
        $service = $this->app->fiscal()->invoiceService();

        $this->assertSame($invoiceId, $service->getVisibleInvoice($this->user('ana@hotelflex.test'), $invoiceId)->id);
        $this->assertDeniedFor(fn () => $service->getVisibleInvoice($this->user('bruno@hotelflex.test'), $invoiceId));
        $this->assertDeniedFor(fn () => $service->cancel($this->user('recepcao.marazul@hotelflex.test'), $invoiceId, 'Tentativa de outro hotel aqui'));
        $this->assertStringContainsString(InvoiceXmlBuilder::SIMULATION_NOTICE, (string) file_get_contents(glob($this->storageRoot . '/storage/fiscal/*.xml')[0]));
    }

    private function processQueueAfter(int $reservationId): int
    {
        $this->processQueue();

        return $this->invoicesOf($reservationId)[0]->id;
    }

    private function createSimulatedIssuer(): SimulatedInvoiceIssuer
    {
        return new SimulatedInvoiceIssuer($this->storageRoot . '/storage/fiscal', new InvoiceXmlBuilder());
    }

    private function assertDeniedFor(callable $action): void
    {
        try {
            $action();
            $this->fail('Acesso deveria ser negado');
        } catch (\HotelFlex\Domain\DomainException $error) {
            $this->assertSame('Registro não encontrado.', $error->getMessage());
        }
    }
}
