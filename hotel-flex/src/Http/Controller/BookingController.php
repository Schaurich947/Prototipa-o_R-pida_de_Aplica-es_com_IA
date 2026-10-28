<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Domain\Clock;
use HotelFlex\Http\Auth;
use HotelFlex\Http\ContextSelector;
use HotelFlex\Http\HttpRequest;
use HotelFlex\Service\BookingSearch;
use HotelFlex\Service\ReservationService;
use HotelFlex\View\View;

final class BookingController
{
    public function __construct(
        private readonly Auth $auth,
        private readonly ContextSelector $context,
        private readonly BookingSearch $search,
        private readonly ReservationService $reservationService,
        private readonly ActionRunner $actions,
        private readonly View $view,
        private readonly Clock $clock,
    ) {
    }

    public function showSearch(HttpRequest $request): void
    {
        $actor = $this->auth->requireUser();
        $guest = $this->context->resolveGuest($actor, $request);
        $checkInDate = HttpRequest::parseDate($request->getQueryString('entrada'));
        $nights = max(1, min(ReservationService::MAX_NIGHTS, $request->getQueryInt('diarias') ?? 1));
        $city = $request->getQueryString('cidade');
        $isValidDate = $checkInDate !== null && $checkInDate >= $this->clock->now()->setTime(0, 0);

        $this->view->render('guest/new-reservation', [
            'pageTitle' => 'Nova reserva',
            'guest' => $guest,
            'guestOptions' => $this->context->getGuestOptions($actor),
            'cities' => $this->search->getCities(),
            'criteria' => ['entrada' => $checkInDate?->format('Y-m-d') ?? '', 'diarias' => $nights, 'cidade' => $city],
            'offers' => $isValidDate ? $this->search->search($checkInDate, $nights, $city) : null,
            'minDate' => $this->clock->now()->format('Y-m-d'),
        ]);
    }

    public function book(HttpRequest $request): never
    {
        $actor = $this->auth->requireUser();
        $guest = $this->context->resolveGuest($actor, $request);
        $this->actions->run(function () use ($guest, $request): string {
            $reservationId = $this->reservationService->book(
                $guest,
                $request->requireBodyInt('room_id', 1, PHP_INT_MAX),
                $request->requireBodyDate('check_in_date'),
                $request->requireBodyInt('nights', 1, ReservationService::MAX_NIGHTS),
            );

            return '/hospede/reservas/' . $reservationId;
        }, 'Reserva confirmada!', '/hospede/nova-reserva');
    }
}
