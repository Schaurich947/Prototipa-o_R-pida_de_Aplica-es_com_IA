<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use HotelFlex\Fiscal\Domain\InvoiceKind;
use HotelFlex\View\View;

/**
 * HTML templates (views/pdf) turned into PDF by Dompdf. Remote files and PHP inside templates are disabled,
 * so data typed by customers can never make the generator fetch URLs or run code.
 */
final class InvoicePdfRenderer
{
    /** 80 mm thermal paper, as used by NFC-e printers (in points). */
    private const RECEIPT_WIDTH_POINTS = 226.77;
    private const RECEIPT_BASE_HEIGHT_POINTS = 600;
    private const RECEIPT_HEIGHT_PER_LINE_POINTS = 28;

    public function __construct(
        private readonly View $view,
        private readonly string $projectRoot,
    ) {
    }

    public function render(InvoicePdfData $data): string
    {
        $isServiceInvoice = $data->invoice->kind === InvoiceKind::Nfse;
        $html = $this->view->renderDocument($isServiceInvoice ? 'danfse' : 'danfe-nfce', ['pdf' => $data]);
        $pdf = new Dompdf($this->createOptions());
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper($isServiceInvoice ? 'A4' : [0, 0, self::RECEIPT_WIDTH_POINTS, $this->getReceiptHeight($data)]);
        $pdf->render();

        return (string) $pdf->output();
    }

    private function createOptions(): Options
    {
        $options = new Options();
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setIsJavascriptEnabled(false);
        $options->setDefaultFont('DejaVu Sans');
        $options->setChroot([$this->projectRoot]);
        $options->setTempDir(sys_get_temp_dir());

        return $options;
    }

    private function getReceiptHeight(InvoicePdfData $data): float
    {
        return self::RECEIPT_BASE_HEIGHT_POINTS + count($data->lines) * self::RECEIPT_HEIGHT_PER_LINE_POINTS;
    }
}
