<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Domain\Clock;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\RequestType;
use HotelFlex\Http\Auth;
use HotelFlex\Http\ContextSelector;
use HotelFlex\Http\HttpRequest;
use HotelFlex\Http\Session;
use HotelFlex\Service\GuestReservationsQuery;
use HotelFlex\Service\RequestService;
use HotelFlex\Service\ReservationService;
use HotelFlex\View\View;

final class GuestReservationsController
{
    private const LIST_PATH = '/hospede/reservas';
    private const MAX_FLEX_HOURS = 12;

    public function __construct(
        private readonly Auth $auth,
        private readonly Session $session,
        private readonly ContextSelector $context,
        private readonly GuestReservationsQuery $queries,
        private readonly ReservationService $reservationService,
        private readonly RequestService $requestService,
        private readonly ActionRunner $actions,
        private readonly View $view,
        private readonly Clock $clock,
    ) {
    }

    public function showList(HttpRequest $request): void
    {
        $actor = $this->auth->requireUser();
        $guest = $this->context->resolveGuest($actor, $request);

        $this->view->render('guest/reservations', [
            'pageTitle' => 'Minhas reservas',
            'guest' => $guest,
            'guestOptions' => $this->context->getGuestOptions($actor),
            'groups' => $this->queries->getGroupedStays($guest),
        ]);
    }

    /** @param array<string, int> $params */
    public function showDetail(HttpRequest $request, array $params): void
    {
        $actor = $this->auth->requireUser();
        $stay = $this->queries->getStay($actor, $params['id']);

        $this->view->render('guest/reservation-detail', [
            'pageTitle' => "Reserva {$stay->reservation->code}",
            'stay' => $stay,
            'requestViews' => $this->queries->getRequestViews($stay),
            'isEditable' => $stay->reservation->isEditableBy($this->clock->now()),
            'old' => $this->session->pullOldInput(),
            'today' => $this->clock->now(),
        ]);
    }

    /** @param array<string, int> $params */
    public function requestHours(HttpRequest $request, array $params): never
    {
        $actor = $this->auth->requireUser();
        $this->actions->run(function () use ($actor, $request, $params): void {
            $type = RequestType::tryFrom($request->getBodyString('type')) ?? throw new DomainException('Tipo de horário inválido.');
            $hours = $request->requireBodyInt('hours', 0, self::MAX_FLEX_HOURS);
            $this->requestService->createRequest($actor, $params['id'], $type, $hours);
        }, 'Solicitação enviada ao hotel.', $this->getDetailPath($params['id']));
    }

    /** @param array<string, int> $params */
    public function changeDates(HttpRequest $request, array $params): never
    {
        $actor = $this->auth->requireUser();
        $this->actions->run(function () use ($actor, $request, $params): void {
            $nights = $request->requireBodyInt('nights', 1, ReservationService::MAX_NIGHTS);
            $this->reservationService->changeDates($actor, $params['id'], $request->requireBodyDate('check_in_date'), $nights);
        }, 'Datas alteradas. Horários flexíveis anteriores foram zerados.', $this->getDetailPath($params['id']), $request->body);
    }

    /** @param array<string, int> $params */
    public function cancel(HttpRequest $request, array $params): never
    {
        $actor = $this->auth->requireUser();
        $this->actions->run(
            fn () => $this->reservationService->cancel($actor, $params['id']),
            'Reserva cancelada.',
            $this->getDetailPath($params['id']),
        );
    }

    private function getDetailPath(int $reservationId): string
    {
        return self::LIST_PATH . '/' . $reservationId;
    }
}
