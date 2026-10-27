<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Domain\HotelPolicy;
use HotelFlex\Http\HttpRequest;
use HotelFlex\Service\HotelQueryService;
use HotelFlex\Service\RequestService;
use HotelFlex\Service\RoomFilter;
use HotelFlex\Service\RoomService;
use HotelFlex\View\View;

final class HotelController
{
    private const RELEASE_PANEL_PATH = '/hotel/liberacao';
    private const REQUESTS_PATH = '/hotel/solicitacoes';

    public function __construct(
        private readonly RoomService $roomService,
        private readonly RequestService $requestService,
        private readonly HotelQueryService $queries,
        private readonly ActionRunner $actions,
        private readonly View $view,
    ) {
    }

    public function showReleasePanel(HttpRequest $request): void
    {
        $filter = RoomFilter::tryFrom((string) ($request->query['filtro'] ?? '')) ?? RoomFilter::All;
        $roomViews = $this->queries->getRoomViews();

        $this->view->render('hotel/release-panel', [
            'pageTitle' => 'Painel de Liberação e Horas Adicionais',
            'roomViews' => $filter->apply($roomViews),
            'allRoomViews' => $roomViews,
            'activeFilter' => $filter,
        ]);
    }

    /** @param array<string, int> $params */
    public function startCleaning(HttpRequest $request, array $params): never
    {
        $this->actions->run(fn () => $this->roomService->startCleaning($params['id']), 'Limpeza iniciada.', $this->getPanelPath($request));
    }

    /** @param array<string, int> $params */
    public function markClean(HttpRequest $request, array $params): never
    {
        $this->actions->run(fn () => $this->roomService->markRoomClean($params['id']), 'Quarto marcado como limpo.', $this->getPanelPath($request));
    }

    /** @param array<string, int> $params */
    public function releaseRoom(HttpRequest $request, array $params): never
    {
        $this->actions->run(fn () => $this->roomService->releaseRoomToGuest($params['id']), 'Quarto liberado para o hóspede.', $this->getPanelPath($request));
    }

    /** @param array<string, int> $params */
    public function updateLateCheckout(HttpRequest $request, array $params): never
    {
        $this->actions->run(function () use ($request, $params): void {
            $hours = $request->requireBodyInt('hours', 0, HotelPolicy::MAX_CHECK_OUT_HOUR - HotelPolicy::STANDARD_CHECK_OUT_HOUR);
            $this->roomService->setLateCheckoutHours($params['id'], $hours);
        }, 'Horas adicionais atualizadas.', $this->getPanelPath($request));
    }

    public function showRequests(): void
    {
        $requestViews = $this->queries->getRequestViews();
        $this->view->render('hotel/requests', [
            'pageTitle' => 'Solicitações dos Hóspedes',
            'pendingViews' => array_values(array_filter($requestViews, static fn ($view) => $view->request->isPending())),
            'resolvedViews' => array_values(array_filter($requestViews, static fn ($view) => !$view->request->isPending())),
        ]);
    }

    /** @param array<string, int> $params */
    public function approveRequest(HttpRequest $request, array $params): never
    {
        $this->actions->run(fn () => $this->requestService->approveRequest($params['id']), 'Solicitação aprovada.', self::REQUESTS_PATH);
    }

    /** @param array<string, int> $params */
    public function rejectRequest(HttpRequest $request, array $params): never
    {
        $this->actions->run(fn () => $this->requestService->rejectRequest($params['id']), 'Solicitação recusada.', self::REQUESTS_PATH);
    }

    /** Keeps the active filter after a POST so the receptionist does not lose context. */
    private function getPanelPath(HttpRequest $request): string
    {
        $filter = RoomFilter::tryFrom($request->getBodyString('filtro'));

        return $filter && $filter !== RoomFilter::All ? self::RELEASE_PANEL_PATH . '?filtro=' . $filter->value : self::RELEASE_PANEL_PATH;
    }
}
