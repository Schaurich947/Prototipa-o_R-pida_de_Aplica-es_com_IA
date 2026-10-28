<?php

declare(strict_types=1);

namespace HotelFlex\Bootstrap;

use HotelFlex\Database\TransactionRunner;
use HotelFlex\Domain\Clock;
use HotelFlex\Domain\SystemClock;
use HotelFlex\Http\Auth;
use HotelFlex\Http\ContextSelector;
use HotelFlex\Http\Controller\ActionRunner;
use HotelFlex\Http\Controller\AuthController;
use HotelFlex\Http\Controller\BookingController;
use HotelFlex\Http\Controller\GuestReservationsController;
use HotelFlex\Http\Controller\HotelPanelController;
use HotelFlex\Http\Controller\HotelRequestsController;
use HotelFlex\Http\Controller\LiveUpdateController;
use HotelFlex\Http\Session;
use HotelFlex\Repository\ChangeTrackerRepository;
use HotelFlex\Repository\HotelRepository;
use HotelFlex\Repository\ReservationRepository;
use HotelFlex\Repository\RoomRepository;
use HotelFlex\Repository\ServiceRequestRepository;
use HotelFlex\Repository\UserRepository;
use HotelFlex\Service\AccessGuard;
use HotelFlex\Service\AuthService;
use HotelFlex\Service\BookingSearch;
use HotelFlex\Service\GuestReservationsQuery;
use HotelFlex\Service\HotelPanelQuery;
use HotelFlex\Service\RequestService;
use HotelFlex\Service\ReservationService;
use HotelFlex\Service\RoomService;
use HotelFlex\Service\StayViewFactory;
use HotelFlex\View\View;
use PDO;

/** Composition root: the only place that wires concrete implementations together. */
final class Container
{
    private readonly Clock $clock;
    private readonly HotelRepository $hotels;
    private readonly UserRepository $users;
    private readonly RoomRepository $rooms;
    private readonly ReservationRepository $reservations;
    private readonly ServiceRequestRepository $requests;
    private readonly StayViewFactory $stays;
    private readonly AccessGuard $guard;
    private readonly TransactionRunner $transaction;
    private readonly Auth $auth;
    private readonly ContextSelector $context;
    private readonly ActionRunner $actions;

    public function __construct(
        private readonly PDO $connection,
        private readonly Session $session,
        private readonly View $view,
    ) {
        $this->clock = new SystemClock();
        $this->hotels = new HotelRepository($connection);
        $this->users = new UserRepository($connection);
        $this->rooms = new RoomRepository($connection);
        $this->reservations = new ReservationRepository($connection);
        $this->requests = new ServiceRequestRepository($connection);
        $this->stays = new StayViewFactory($this->hotels, $this->rooms, $this->reservations, $this->users);
        $this->guard = new AccessGuard($this->reservations, $this->rooms, $this->requests);
        $this->transaction = new TransactionRunner($connection);
        $this->auth = new Auth($session, $this->users);
        $this->context = new ContextSelector($session, $this->hotels, $this->users);
        $this->actions = new ActionRunner($session);
    }

    public function auth(): Auth
    {
        return $this->auth;
    }

    public function authController(): AuthController
    {
        return new AuthController($this->session, $this->auth, new AuthService($this->users), $this->actions, $this->view);
    }

    public function hotelPanelController(): HotelPanelController
    {
        return new HotelPanelController($this->auth, $this->context, $this->roomService(), $this->panelQuery(), $this->actions, $this->view, $this->clock);
    }

    public function hotelRequestsController(): HotelRequestsController
    {
        return new HotelRequestsController($this->auth, $this->context, $this->requestService(), $this->panelQuery(), $this->actions, $this->view);
    }

    public function guestReservationsController(): GuestReservationsController
    {
        $queries = new GuestReservationsQuery($this->guard, $this->stays, $this->requests, $this->reservations);

        return new GuestReservationsController(
            $this->auth, $this->session, $this->context, $queries, $this->reservationService(), $this->requestService(), $this->actions, $this->view, $this->clock,
        );
    }

    public function bookingController(): BookingController
    {
        $search = new BookingSearch($this->rooms, $this->reservations, $this->hotels, $this->stays);

        return new BookingController($this->auth, $this->context, $search, $this->reservationService(), $this->actions, $this->view, $this->clock);
    }

    public function liveUpdateController(): LiveUpdateController
    {
        return new LiveUpdateController(new ChangeTrackerRepository($this->connection));
    }

    private function roomService(): RoomService
    {
        return new RoomService($this->rooms, $this->reservations, $this->requests, $this->guard, $this->stays, $this->transaction, $this->clock);
    }

    private function requestService(): RequestService
    {
        return new RequestService($this->requests, $this->guard, $this->stays, $this->roomService(), $this->transaction, $this->clock);
    }

    private function reservationService(): ReservationService
    {
        return new ReservationService($this->reservations, $this->rooms, $this->requests, $this->guard, $this->stays, $this->transaction, $this->clock);
    }

    private function panelQuery(): HotelPanelQuery
    {
        return new HotelPanelQuery($this->rooms, $this->reservations, $this->requests, $this->stays);
    }
}
