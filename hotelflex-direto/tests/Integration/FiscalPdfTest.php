<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Integration;

use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\PaymentMethod;
use HotelFlex\Fiscal\Domain\InvoiceSource;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Objetivo: cada nota autorizada vira um PDF no layout oficial (DANFSe A4 para serviço, DANFE NFC-e 80 mm para o café),
 * marcado como simulação, só para quem pode ver a nota, e com numeração própria para cada modelo.
 */
#[TestDox('PDF das notas (DANFSe e DANFE NFC-e)')]
final class FiscalPdfTest extends FiscalTestCase
{
    private const CONSULT_URL = 'http://localhost/hotelflex-direto/notas/1';

    #[TestDox('Hospedagem gera DANFSe em PDF A4 de 1 página, com o nome do arquivo marcado como simulação')]
    public function testServiceInvoiceBecomesDanfse(): void
    {
        $reservationId = $this->stayAndCheckOut('ana@hotelflex.test', 1);
        $this->processQueue();

        $pdf = $this->generate('ana@hotelflex.test', $this->invoicesOf($reservationId)[0]->id);

        $this->assertStringStartsWith('%PDF-', $pdf['content']);
        $this->assertSame('DANFSe-1-000001-SIMULACAO.pdf', $pdf['fileName']);
        $this->assertSame(1, preg_match_all('#/Type\s*/Page[^s]#', $pdf['content']));
    }

    #[TestDox('Café gera DANFE NFC-e com numeração própria (nº 1), separada da numeração das NFS-e')]
    public function testConsumerInvoiceHasItsOwnNumbering(): void
    {
        $this->processQueueAfterStay();
        $this->clock->travelTo('2026-12-02 08:00:00');
        $this->app->breakfastService()->sellWalkIn($this->user('recepcao.centro@hotelflex.test'), 1, 2, PaymentMethod::Cash, 'Pedro', '390.533.447-05');
        $this->processQueue();
        $invoice = $this->app->fiscal()->invoiceService()->findByHotelAndMonth(1, '2026-12')[0];

        $pdf = $this->generate('recepcao.centro@hotelflex.test', $invoice->id);

        $this->assertSame('nfce', $invoice->kind->value);
        $this->assertSame(1, $invoice->number);
        $this->assertSame('DANFE-NFCe-1-000001-SIMULACAO.pdf', $pdf['fileName']);
        $this->assertStringStartsWith('%PDF-', $pdf['content']);
    }

    #[TestDox('Sem autorização não há PDF; e outro hóspede não baixa o PDF da nota de alguém')]
    public function testPdfOnlyForAuthorizedInvoicesAndAllowedPeople(): void
    {
        $reservationId = $this->stayAndCheckOut('ana@hotelflex.test', 1);
        $invoiceId = $this->invoicesOf($reservationId)[0]->id;
        $this->assertDenied(fn () => $this->generate('ana@hotelflex.test', $invoiceId), 'depois que a nota é autorizada');

        $this->processQueue();

        $this->assertDenied(fn () => $this->generate('bruno@hotelflex.test', $invoiceId), 'Registro não encontrado.');
    }

    private function processQueueAfterStay(): void
    {
        $this->stayAndCheckOut('ana@hotelflex.test', 1);
        $this->processQueue();
        $this->assertSame(1, $this->app->fiscal()->invoiceService()->findBySource(InvoiceSource::Reservation, 1)[0]->number);
    }

    /** @return array{fileName: string, content: string} */
    private function generate(string $email, int $invoiceId): array
    {
        return $this->app->fiscal()->invoicePdfService(new \HotelFlex\View\View(dirname(__DIR__, 2) . '/views'))->generate($this->user($email), $invoiceId, self::CONSULT_URL);
    }

    private function assertDenied(callable $action, string $message): void
    {
        try {
            $action();
            $this->fail('Deveria ser recusado');
        } catch (DomainException $error) {
            $this->assertStringContainsString($message, $error->getMessage());
        }
    }
}
