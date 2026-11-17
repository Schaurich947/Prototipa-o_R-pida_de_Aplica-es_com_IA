<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Domain\Clock;
use HotelFlex\Domain\DomainException;
use HotelFlex\Fiscal\Issuer\InvoiceXmlBuilder;
use HotelFlex\Fiscal\Service\HotelFiscalService;
use HotelFlex\Fiscal\Service\InvoiceProcessor;
use HotelFlex\Fiscal\Service\InvoiceService;
use HotelFlex\Fiscal\Service\MonthlyTaxSummary;
use HotelFlex\Http\Auth;
use HotelFlex\Http\ContextSelector;
use HotelFlex\Http\HttpRequest;
use HotelFlex\Repository\HotelRepository;
use HotelFlex\View\View;

/** Invoices: the hotel's monthly list and tax pre-control, and the invoice document for whoever may see it. */
final class InvoiceController
{
    private const LIST_PATH = '/hotel/notas';

    public function __construct(
        private readonly Auth $auth,
        private readonly ContextSelector $context,
        private readonly InvoiceService $invoices,
        private readonly InvoiceProcessor $processor,
        private readonly MonthlyTaxSummary $summary,
        private readonly HotelFiscalService $fiscal,
        private readonly HotelRepository $hotels,
        private readonly ActionRunner $actions,
        private readonly View $view,
        private readonly Clock $clock,
    ) {
    }

    public function showHotelList(HttpRequest $request): void
    {
        $actor = $this->auth->requireUser();
        $hotel = $this->context->resolveHotel($actor, $request);
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $request->getQueryString('mes')) ? $request->getQueryString('mes') : $this->clock->now()->format('Y-m');
        $invoices = $this->invoices->findByHotelAndMonth($hotel->id, $month);

        $this->view->render('hotel/invoices', [
            'pageTitle' => 'Notas fiscais',
            'hotel' => $hotel,
            'hotelOptions' => $this->context->getHotelOptions($actor),
            'month' => $month,
            'invoices' => $invoices,
            'summary' => $this->summary->summarize($hotel->id, $month, $invoices),
            'hasFiscalProfile' => $this->fiscal->find($hotel->id) !== null,
        ]);
    }

    public function processQueue(HttpRequest $request): never
    {
        $actor = $this->auth->requireUser();
        $hotelId = $request->requireBodyInt('hotel_id', 1, PHP_INT_MAX);
        $this->actions->run(function () use ($actor, $hotelId): void {
            if (!$actor->canManageHotel($hotelId)) {
                throw new DomainException('Registro não encontrado.');
            }
            $result = $this->processor->processDue($hotelId);
            if ($result['rejected'] > 0) {
                throw new DomainException("{$result['authorized']} nota(s) autorizada(s) e {$result['rejected']} rejeitada(s). Veja o motivo na lista.");
            }
        }, 'Fila de notas processada.', self::LIST_PATH . "?hotel={$hotelId}");
    }

    /** @param array<string, int> $params */
    public function cancel(HttpRequest $request, array $params): never
    {
        $actor = $this->auth->requireUser();
        $this->actions->run(fn () => $this->invoices->cancel($actor, $params['id'], $request->getBodyString('reason')), 'Nota cancelada. O estorno dos tributos foi lançado.', self::LIST_PATH . '?mes=' . rawurlencode($request->getBodyString('month')));
    }

    /** @param array<string, int> $params */
    public function requeue(HttpRequest $request, array $params): never
    {
        $actor = $this->auth->requireUser();
        $this->actions->run(fn () => $this->invoices->requeue($actor, $params['id']), 'Nota de volta à fila de emissão.', self::LIST_PATH . '?mes=' . rawurlencode($request->getBodyString('month')));
    }

    /** @param array<string, int> $params */
    public function showDocument(HttpRequest $request, array $params): void
    {
        $invoice = $this->invoices->getVisibleInvoice($this->auth->requireUser(), $params['id']);
        $this->view->render('invoice-document', [
            'pageTitle' => "{$invoice->kind->label()} {$invoice->formatNumber()}",
            'invoice' => $invoice,
            'hotel' => $this->hotels->findById($invoice->hotelId),
            'issuer' => $this->fiscal->find($invoice->hotelId),
        ], 'document');
    }

    /** @param array<string, int> $params */
    public function downloadXml(HttpRequest $request, array $params): never
    {
        $invoice = $this->invoices->getVisibleInvoice($this->auth->requireUser(), $params['id']);
        $issuer = $this->fiscal->find($invoice->hotelId);
        if ($invoice->accessKey === null || $issuer === null) {
            throw new DomainException('A nota ainda não foi autorizada.');
        }
        header('Content-Type: application/xml; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$invoice->kind->value}-{$invoice->formatNumber()}-SIMULACAO.xml\"");
        header('Cache-Control: private, no-store');
        echo (new InvoiceXmlBuilder())->build($invoice, $issuer, $invoice->accessKey);
        exit;
    }
}
