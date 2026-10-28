<?php

declare(strict_types=1);

namespace HotelFlex\Bootstrap;

use HotelFlex\Config\Env;
use HotelFlex\Database\ConnectionFactory;
use HotelFlex\Database\TransactionRunner;
use HotelFlex\Domain\SystemClock;
use HotelFlex\Http\Controller\ActionRunner;
use HotelFlex\Http\Controller\AuthController;
use HotelFlex\Http\Controller\GuestController;
use HotelFlex\Http\Controller\HotelController;
use HotelFlex\Http\Controller\LiveUpdateController;
use HotelFlex\Http\HttpRequest;
use HotelFlex\Http\Response;
use HotelFlex\Http\Router;
use HotelFlex\Http\Session;
use HotelFlex\Http\Url;
use HotelFlex\Repository\ChangeTrackerRepository;
use HotelFlex\Repository\ReservationRepository;
use HotelFlex\Repository\RoomRepository;
use HotelFlex\Repository\ServiceRequestRepository;
use HotelFlex\Service\HotelQueryService;
use HotelFlex\Service\RequestService;
use HotelFlex\Service\RoomService;
use HotelFlex\View\View;

/** Composition root: the only place that wires concrete implementations together. */
final class App
{
    public static function run(string $projectRoot): void
    {
        $env = Env::load($projectRoot . '/.env');
        date_default_timezone_set($env->get('APP_TIMEZONE', 'America/Sao_Paulo'));
        Url::setBasePath(Url::detectBasePath($_SERVER));
        $view = new View($projectRoot . '/views');
        ErrorHandler::register($view, $env->getBool('APP_DEBUG'));

        Response::sendSecurityHeaders();
        Session::start(($_SERVER['HTTPS'] ?? 'off') !== 'off');
        $session = new Session();
        $router = self::buildRouter($env, $session, $view);

        $view->share('currentUser', $session->getUser());
        $view->share('csrfToken', $session->getCsrfToken());
        $view->share('flashes', $session->pullFlashes());

        $request = HttpRequest::fromGlobals();
        $view->share('currentPath', $request->path);
        if (!$router->dispatch($request)) {
            ErrorHandler::renderNotFound($view);
        }
    }

    private static function buildRouter(Env $env, Session $session, View $view): Router
    {
        $connection = ConnectionFactory::createAppConnection($env);
        $clock = new SystemClock();
        $rooms = new RoomRepository($connection);
        $reservations = new ReservationRepository($connection);
        $requests = new ServiceRequestRepository($connection);

        $roomService = new RoomService($rooms, $reservations, $clock);
        $requestService = new RequestService($requests, $reservations, $rooms, $roomService, new TransactionRunner($connection), $clock);
        $queries = new HotelQueryService($rooms, $reservations, $requests);
        $actions = new ActionRunner($session);

        $router = new Router($session);
        Routes::register(
            $router,
            new AuthController($session, $queries, $view),
            new HotelController($roomService, $requestService, $queries, $actions, $view),
            new GuestController($session, $requestService, $queries, $actions, $view),
            new LiveUpdateController(new ChangeTrackerRepository($connection)),
        );

        return $router;
    }
}
