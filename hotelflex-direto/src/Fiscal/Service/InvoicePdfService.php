<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Service;

use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\User;
use HotelFlex\Fiscal\Domain\Invoice;
use HotelFlex\Fiscal\Domain\InvoiceKind;
use HotelFlex\Fiscal\Domain\InvoiceSource;
use HotelFlex\Fiscal\Pdf\InvoiceLine;
use HotelFlex\Fiscal\Pdf\InvoicePdfData;
use HotelFlex\Fiscal\Pdf\InvoicePdfRenderer;
use HotelFlex\Fiscal\Pdf\QrCodeImage;
use HotelFlex\Fiscal\Repository\HotelFiscalProfileRepository;
use HotelFlex\Repository\BreakfastRepository;
use HotelFlex\Repository\HotelRepository;

/** DANFSe (service invoice) and DANFE NFC-e (consumer invoice) in PDF, for whoever may see the invoice. */
final class InvoicePdfService
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly HotelFiscalProfileRepository $fiscalProfiles,
        private readonly HotelRepository $hotels,
        private readonly BreakfastRepository $breakfasts,
        private readonly InvoicePdfRenderer $renderer,
    ) {
    }

    /** @return array{fileName: string, content: string} */
    public function generate(User $actor, int $invoiceId, string $consultUrl): array
    {
        $invoice = $this->invoices->getVisibleInvoice($actor, $invoiceId);
        if ($invoice->accessKey === null) {
            throw new DomainException('O PDF fica disponível depois que a nota é autorizada.');
        }
        $issuer = $this->fiscalProfiles->findByHotel($invoice->hotelId) ?? throw new DomainException('Dados fiscais do hotel não cadastrados.');
        $hotel = $this->hotels->findById($invoice->hotelId) ?? throw new DomainException('Hotel não encontrado.');
        [$lines, $discountCents, $paymentLabel] = $this->getSaleDetails($invoice);
        $data = new InvoicePdfData($invoice, $issuer, $hotel, $lines, $discountCents, $paymentLabel, $consultUrl, QrCodeImage::toDataUri($consultUrl));

        return ['fileName' => self::buildFileName($invoice), 'content' => $this->renderer->render($data)];
    }

    /** @return array{0: list<InvoiceLine>, 1: int, 2: string} items, discount and payment method */
    private function getSaleDetails(Invoice $invoice): array
    {
        $booking = $invoice->source === InvoiceSource::Breakfast ? $this->breakfasts->findById($invoice->sourceId) : null;
        if ($booking === null) {
            return [[new InvoiceLine('001', $invoice->description, 1, $invoice->amountCents, $invoice->amountCents)], 0, 'Pix'];
        }
        $grossCents = $booking->unitPriceCents * $booking->people;
        $line = new InvoiceLine('001', 'CAFÉ DA MANHÃ ' . $booking->date->format('d/m/Y'), $booking->people, $booking->unitPriceCents, $grossCents);

        return [[$line], $grossCents - $booking->totalCents, $booking->paidMethod?->label() ?? 'Outros'];
    }

    private static function buildFileName(Invoice $invoice): string
    {
        $prefix = $invoice->kind === InvoiceKind::Nfse ? 'DANFSe' : 'DANFE-NFCe';

        return "{$prefix}-{$invoice->formatNumber()}-SIMULACAO.pdf";
    }
}
