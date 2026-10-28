<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Domain\Clock;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\RequestType;
use HotelFlex\Http\Auth;
use HotelFlex\Http\ContextSelector;
use HotelFlex\Http\HttpRequest;
use HotelFlex\Service\HotelPanelQuery;
use HotelFlex\Service\RoomPanelFilter;
use HotelFlex\Service\RoomService;
use HotelFlex\View\View;

final class HotelPanelController
{
    private const PANEL_PATH = '/hotel/liberacao';
    private const MAX_FLEX_HOURS = 12;

    public function __construct(
        private readonly Auth $auth,
        private readonly ContextSelector $context,
        private readonly RoomService $roomService,
        private readonly HotelPanelQuery $queries,
        private readonly ActionRunner $actions,
        private readonly View $view,
        private readonly Clock $clock,
    ) {
    }

    public function showPanel(HttpRequest $request): void
    {
        $actor = $this->auth->requireUser();
        $hotel = $this->context->resolveHotel($actor, $request);
        $filter = RoomPanelFilter::tryFrom($request->getQueryString('filtro')) ?? RoomPanelFilter::All;
        $cards = $this->queries->getRoomCards($hotel, $this->clock->now()->setTime(0, 0));

        $this->view->render('hotel/release-panel', [
            'pageTitle' => 'Painel do dia',
            'hotel' => $hotel,
            'hotelOptions' => $this->context->getHotelOptions($actor),
            'cards' => $filter->apply($cards),
            'allCards' => $cards,
            'activeFilter' => $filter,
            'today' => $this->clock->now(),
        ]);
    }

    /** @param array<string, int> $params */
    public function startCleaning(HttpRequest $request, array $params): never
    {
        $this->runRoomAction($request, fn ($actor) => $this->roomService->startCleaning($actor, $params['id']), 'Limpeza iniciada.');
    }

    /** @param array<string, int> $params */
    public function markClean(HttpRequest $request, array $params): never
    {
        $this->runRoomAction($request, fn ($actor) => $this->roomService->markRoomClean($actor, $params['id']), 'Quarto marcado como limpo.');
    }

    /** @param array<string, int> $params */
    public function releaseRoom(HttpRequest $request, array $params): never
    {
        $this->runRoomAction($request, fn ($actor) => $this->roomService->releaseRoomToGuest($actor, $params['id']), 'Quarto liberado para o hóspede.');
    }

    /** @param array<string, int> $params */
    public function checkIn(HttpRequest $request, array $params): never
    {
        $this->runRoomAction($request, fn ($actor) => $this->roomService->checkIn($actor, $params['id']), 'Check-in registrado.');
    }

    /** @param array<string, int> $params */
    public function checkOut(HttpRequest $request, array $params): never
    {
        $this->runRoomAction($request, fn ($actor) => $this->roomService->checkOut($actor, $params['id']), 'Check-out registrado. Quarto enviado para limpeza.');
    }

    /** @param array<string, int> $params */
    public function updateHours(HttpRequest $request, array $params): never
    {
        $this->runRoomAction($request, function ($actor) use ($request, $params): void {
            $type = RequestType::tryFrom($request->getBodyString('type')) ?? throw new DomainException('Tipo de horário inválido.');
            $hours = $request->requireBodyInt('hours', 0, self::MAX_FLEX_HOURS);
            $this->roomService->setFlexibleHours($actor, $params['id'], $type, $hours);
        }, 'Horário atualizado.');
    }

    private function runRoomAction(HttpRequest $request, callable $action, string $successMessage): never
    {
        $actor = $this->auth->requireUser();
        $this->actions->run(static fn () => $action($actor), $successMessage, $this->getPanelPath($request));
    }

    /** Keeps the active filter after a POST so the receptionist does not lose context. */
    private function getPanelPath(HttpRequest $request): string
    {
        $filter = RoomPanelFilter::tryFrom($request->getBodyString('filtro'));

        return $filter && $filter !== RoomPanelFilter::All ? self::PANEL_PATH . '?filtro=' . $filter->value : self::PANEL_PATH;
    }
}
