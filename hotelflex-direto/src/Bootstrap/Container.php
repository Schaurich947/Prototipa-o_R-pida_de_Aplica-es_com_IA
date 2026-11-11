<?php

declare(strict_types=1);

namespace HotelFlex\Bootstrap;

use HotelFlex\Config\Env;
use HotelFlex\Database\TransactionRunner;
use HotelFlex\Domain\Clock;
use HotelFlex\Domain\SystemClock;
use HotelFlex\Http\Auth;
use HotelFlex\Http\ContextSelector;
use HotelFlex\Http\Controller\ActionRunner;
use HotelFlex\Http\Controller\AuthController;
use HotelFlex\Http\Controller\BookingController;
use HotelFlex\Http\Controller\BreakfastController;
use HotelFlex\Http\Controller\GuestReservationsController;
use HotelFlex\Http\Controller\HotelPanelController;
use HotelFlex\Http\Controller\HotelRequestsController;
use HotelFlex\Http\Controller\LiveUpdateController;
use HotelFlex\Http\Controller\PaymentsController;
use HotelFlex\Http\Controller\ProfileController;
use HotelFlex\Http\Session;
use HotelFlex\Notification\SenderFactory;
use HotelFlex\Repository\BreakfastRepository;
use HotelFlex\Repository\ChangeTrackerRepository;
use HotelFlex\Repository\ChargeRepository;
use HotelFlex\Repository\NotificationRepository;
use HotelFlex\Repository\HotelRepository;
use HotelFlex\Repository\ReservationRepository;
use HotelFlex\Repository\RoomRepository;
use HotelFlex\Repository\ServiceRequestRepository;
use HotelFlex\Repository\UserRepository;
use HotelFlex\Service\AccessGuard;
use HotelFlex\Service\AuthService;
use HotelFlex\Service\BookingSearch;
use HotelFlex\Service\BreakfastRowBuilder;
use HotelFlex\Service\BreakfastService;
use HotelFlex\Service\ChargeService;
use HotelFlex\Service\GuestReservationsQuery;
use HotelFlex\Service\HotelPanelQuery;
use HotelFlex\Service\NotificationDispatcher;
use HotelFlex\Service\Notifier;
use HotelFlex\Service\ReceiptStorage;
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

    private readonly ChargeRepository $charges;
    private readonly NotificationRepository $notifications;
    private readonly ReceiptStorage $receipts;
    private readonly SenderFactory $senders;

    public function __construct(
        private readonly PDO $connection,
        private readonly Env $env,
        private readonly Session $session,
        private readonly View $view,
        string $projectRoot,
    ) {
        $this->clock = new SystemClock();
        $this->hotels = new HotelRepository($connection);
        $this->users = new UserRepository($connection);
        $this->rooms = new RoomRepository($connection);
        $this->reservations = new ReservationRepository($connection);
        $this->requests = new ServiceRequestRepository($connection);
        $this->stays = new StayViewFactory($this->hotels, $this->rooms, $this->reservations, $this->users, new ChargeRepository($connection));
        $this->guard = new AccessGuard($this->reservations, $this->rooms, $this->requests);
        $this->transaction = new TransactionRunner($connection);
        $this->auth = new Auth($session, $this->users);
        $this->context = new ContextSelector($session, $this->hotels, $this->users);
        $this->actions = new ActionRunner($session);
        $this->charges = new ChargeRepository($connection);
        $this->notifications = new NotificationRepository($connection);
        $this->receipts = new ReceiptStorage($projectRoot . '/storage/receipts');
        $this->senders = new SenderFactory($env, $projectRoot . '/storage/outbox');
    }

    public function auth(): Auth
    {
        return $this->auth;
    }

    public function authController(): AuthController
    {
        return new AuthController($this->session, $this->auth, new AuthService($this->users, $this->clock), $this->actions, $this->view);
    }

    public function hotelPanelController(): HotelPanelController
    {
        return new HotelPanelController($this->auth, $this->context, $this->roomService(), $this->panelQuery(), $this->chargeService(), $this->actions, $this->view, $this->clock);
    }

    public function hotelRequestsController(): HotelRequestsController
    {
        return new HotelRequestsController($this->auth, $this->context, $this->requestService(), $this->panelQuery(), $this->actions, $this->view);
    }

    public function guestReservationsController(): GuestReservationsController
    {
        $queries = new GuestReservationsQuery($this->guard, $this->stays, $this->requests, $this->reservations, $this->charges, $this->notifications);

        return new GuestReservationsController(
            $this->auth, $this->session, $this->context, $queries, $this->reservationService(), $this->requestService(), $this->actions, $this->view, $this->clock,
        );
    }

    public function bookingController(): BookingController
    {
        $search = new BookingSearch($this->rooms, $this->reservations, $this->hotels, $this->stays);

        return new BookingController($this->auth, $this->context, $search, $this->reservationService(), $this->actions, $this->view, $this->clock);
    }

    public function paymentsController(): PaymentsController
    {
        return new PaymentsController($this->auth, $this->context, $this->chargeService(), $this->panelQuery(), $this->receipts, $this->actions, $this->view);
    }

    public function profileController(): ProfileController
    {
        return new ProfileController($this->auth, new AuthService($this->users, $this->clock), $this->actions, $this->view);
    }

    /** Also used by the CLI worker. */
    public function dispatcher(): NotificationDispatcher
    {
        return new NotificationDispatcher(
            $this->notifications, $this->users, $this->senders->createEmailSender(), $this->senders->createWhatsAppSender(),
            $this->transaction, $this->clock, $this->senders->getWhatsAppTemplate(), $this->senders->getWhatsAppLanguage(),
        );
    }

    public function chargeService(): ChargeService
    {
        return new ChargeService($this->charges, $this->reservations, $this->guard, $this->stays, $this->receipts, $this->notifier(), $this->transaction, $this->clock);
    }

    public function breakfastController(): BreakfastController
    {
        return new BreakfastController(
            $this->auth, $this->context, $this->breakfastService(), new BreakfastRepository($this->connection), $this->hotels, $this->users, $this->receipts, $this->actions, $this->view, $this->clock,
        );
    }

    /** Also used by the CLI worker. */
    public function breakfastService(): BreakfastService
    {
        $bookings = new BreakfastRepository($this->connection);

        return new BreakfastService($bookings, new BreakfastRowBuilder($bookings, $this->clock), $this->hotels, $this->reservations, $this->receipts, $this->notifier(), $this->transaction, $this->clock);
    }

    public function liveUpdateController(): LiveUpdateController
    {
        return new LiveUpdateController(new ChangeTrackerRepository($this->connection));
    }

    private function roomService(): RoomService
    {
        return new RoomService($this->rooms, $this->reservations, $this->requests, $this->guard, $this->stays, $this->chargeService(), $this->notifier(), $this->transaction, $this->clock);
    }

    private function requestService(): RequestService
    {
        return new RequestService($this->requests, $this->guard, $this->stays, $this->roomService(), $this->notifier(), $this->transaction, $this->clock);
    }

    private function reservationService(): ReservationService
    {
        return new ReservationService($this->reservations, $this->rooms, $this->requests, $this->guard, $this->stays, $this->chargeService(), $this->notifier(), $this->transaction, $this->clock);
    }

    private function notifier(): Notifier
    {
        return new Notifier($this->notifications, $this->users);
    }

    private function panelQuery(): HotelPanelQuery
    {
        return new HotelPanelQuery($this->rooms, $this->reservations, $this->requests, $this->charges, $this->stays);
    }
}
