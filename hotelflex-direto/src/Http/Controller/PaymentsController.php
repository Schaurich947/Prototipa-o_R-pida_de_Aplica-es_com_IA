<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\PaymentMethod;
use HotelFlex\Http\Auth;
use HotelFlex\Http\ContextSelector;
use HotelFlex\Http\HttpRequest;
use HotelFlex\Service\ChargeService;
use HotelFlex\Service\HotelPanelQuery;
use HotelFlex\Service\ReceiptStorage;
use HotelFlex\View\View;

/** Pix receipts: the guest sends, the reception checks, both can download. */
final class PaymentsController
{
    private const HOTEL_PATH = '/hotel/pagamentos';

    public function __construct(
        private readonly Auth $auth,
        private readonly ContextSelector $context,
        private readonly ChargeService $charges,
        private readonly HotelPanelQuery $queries,
        private readonly ReceiptStorage $receipts,
        private readonly ActionRunner $actions,
        private readonly View $view,
    ) {
    }

    public function showHotelPayments(HttpRequest $request): void
    {
        $actor = $this->auth->requireUser();
        $hotel = $this->context->resolveHotel($actor, $request);
        $this->charges->expireOverdue();

        $this->view->render('hotel/payments', [
            'pageTitle' => 'Pagamentos',
            'hotel' => $hotel,
            'hotelOptions' => $this->context->getHotelOptions($actor),
            'items' => $this->queries->getChargesToReview($hotel),
            'openItems' => $this->queries->getOpenCharges($hotel),
        ]);
    }

    /** @param array<string, int> $params */
    public function submitReceipt(HttpRequest $request, array $params): never
    {
        $actor = $this->auth->requireUser();
        $returnTo = '/hospede/reservas/' . (int) ($request->body['reservation_id'] ?? 0);
        $file = is_array($request->files['receipt'] ?? null) ? $request->files['receipt'] : [];
        $this->actions->run(
            fn () => $this->charges->submitReceipt($actor, $params['id'], $file),
            'Comprovante enviado. O hotel vai conferir e você recebe a confirmação por mensagem.',
            $returnTo,
        );
    }

    /** @param array<string, int> $params */
    public function approve(HttpRequest $request, array $params): never
    {
        $actor = $this->auth->requireUser();
        $this->actions->run(fn () => $this->charges->reviewReceipt($actor, $params['id'], true, ''), 'Pagamento aprovado. O hóspede foi avisado.', self::HOTEL_PATH);
    }

    /** @param array<string, int> $params */
    public function reject(HttpRequest $request, array $params): never
    {
        $actor = $this->auth->requireUser();
        $this->actions->run(
            fn () => $this->charges->reviewReceipt($actor, $params['id'], false, $request->getBodyString('reason')),
            'Comprovante recusado. O hóspede recebeu o motivo e pode enviar outro.',
            self::HOTEL_PATH,
        );
    }

    /** @param array<string, int> $params */
    public function receiveAtDesk(HttpRequest $request, array $params): never
    {
        $actor = $this->auth->requireUser();
        $this->actions->run(function () use ($actor, $request, $params): void {
            $method = PaymentMethod::tryFrom($request->getBodyString('method')) ?? throw new DomainException('Escolha como o pagamento foi recebido.');
            $this->charges->receiveAtDesk($actor, $params['id'], $method);
        }, 'Recebimento registrado. O hóspede foi avisado.', self::HOTEL_PATH);
    }

    /** @param array<string, int> $params */
    public function markRefunded(HttpRequest $request, array $params): never
    {
        $actor = $this->auth->requireUser();
        $returnTo = '/hospede/reservas/' . (int) ($request->body['reservation_id'] ?? 0);
        $this->actions->run(fn () => $this->charges->markRefunded($actor, $params['id']), 'Devolução registrada.', $returnTo);
    }

    /** @param array<string, int> $params */
    public function downloadReceipt(HttpRequest $request, array $params): never
    {
        $charge = $this->charges->getChargeForViewer($this->auth->requireUser(), $params['id']);
        if ($charge->receiptPath === null) {
            throw new DomainException('Esta cobrança não tem comprovante.');
        }
        ReceiptDownload::send($this->receipts->getAbsolutePath($charge->receiptPath), (string) $charge->receiptOriginalName, (string) $charge->receiptMime);
    }
}
