<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\HotelPolicy;
use HotelFlex\Domain\RequestType;
use HotelFlex\Domain\Reservation;
use HotelFlex\Domain\Role;
use HotelFlex\Http\HttpRequest;
use HotelFlex\Http\Session;
use HotelFlex\Http\SessionUser;
use HotelFlex\Service\HotelQueryService;
use HotelFlex\Service\RequestService;
use HotelFlex\View\View;

final class GuestController
{
    public function __construct(
        private readonly Session $session,
        private readonly RequestService $requestService,
        private readonly HotelQueryService $queries,
        private readonly ActionRunner $actions,
        private readonly View $view,
    ) {
    }

    public function showReservation(HttpRequest $request): void
    {
        $reservation = $this->resolveReservation($this->getUser(), $request->getQueryInt('reserva'));
        $this->view->render('guest/reservation', [
            'pageTitle' => 'Minha Reserva',
            ...$this->buildGuestData($reservation),
        ]);
    }

    public function showAvailability(HttpRequest $request): void
    {
        $reservation = $this->resolveReservation($this->getUser(), $request->getQueryInt('reserva'));
        $this->view->render('guest/availability', [
            'pageTitle' => 'Disponibilidade',
            ...$this->buildGuestData($reservation),
        ]);
    }

    public function createRequest(HttpRequest $request): never
    {
        $requestedId = filter_var($request->body['reservation_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
        $reservation = $this->resolveReservation($this->getUser(), $requestedId);
        $redirectTo = '/hospede/disponibilidade' . ($this->getUser()->role === Role::Admin && $reservation ? '?reserva=' . $reservation->id : '');

        $this->actions->run(function () use ($request, $reservation): void {
            if ($reservation === null) {
                throw new DomainException('Reserva não encontrada.');
            }
            $type = RequestType::tryFrom($request->getBodyString('type')) ?? throw new DomainException('Tipo de solicitação inválido.');
            $maxHours = HotelPolicy::MAX_CHECK_OUT_HOUR - HotelPolicy::STANDARD_CHECK_OUT_HOUR;
            $hours = $type === RequestType::LateCheckout ? $request->requireBodyInt('hours', 1, $maxHours) : 0;
            $this->requestService->createRequest($reservation->id, $type, $hours);
        }, 'Solicitação enviada à recepção.', $redirectTo);
    }

    /**
     * Guests are pinned to the reservation stored in their session, whatever the
     * request says; only admins can choose another reservation.
     */
    private function resolveReservation(SessionUser $user, ?int $requestedId): ?Reservation
    {
        if ($user->role !== Role::Admin) {
            return $user->reservationId ? $this->queries->findReservation($user->reservationId) : null;
        }
        $reservation = $requestedId ? $this->queries->findReservation($requestedId) : null;

        return $reservation ?? ($this->queries->getReservations()[0] ?? null);
    }

    /** @return array<string, mixed> */
    private function buildGuestData(?Reservation $reservation): array
    {
        $isAdmin = $this->getUser()->role === Role::Admin;

        return [
            'reservation' => $reservation,
            'roomView' => $reservation ? $this->queries->getRoomViewForReservation($reservation) : null,
            'requestViews' => $reservation ? $this->queries->getRequestViewsForReservation($reservation) : [],
            'isAdmin' => $isAdmin,
            'reservations' => $isAdmin ? $this->queries->getReservations() : [],
        ];
    }

    /** Routes only reach this controller with a signed-in user. */
    private function getUser(): SessionUser
    {
        return $this->session->getUser() ?? throw new DomainException('Sessão expirada.');
    }
}
