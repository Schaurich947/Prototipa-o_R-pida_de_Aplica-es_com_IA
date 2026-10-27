<?php

declare(strict_types=1);

namespace HotelFlex\Bootstrap;

use HotelFlex\Http\Controller\AuthController;
use HotelFlex\Http\Controller\GuestController;
use HotelFlex\Http\Controller\HotelController;
use HotelFlex\Http\Controller\LiveUpdateController;
use HotelFlex\Http\Navigation;
use HotelFlex\Http\Response;
use HotelFlex\Http\Router;

final class Routes
{
    public static function register(
        Router $router,
        AuthController $auth,
        HotelController $hotel,
        GuestController $guest,
        LiveUpdateController $live,
    ): void {
        $router->get('/', static fn () => Response::redirect('/login'), null);
        $router->get('/login', $auth->showLogin(...), null);
        $router->post('/login', $auth->login(...), null);
        $router->post('/logout', $auth->logout(...), Navigation::SIGNED_IN_ROLES);

        $router->get('/hotel/liberacao', $hotel->showReleasePanel(...), Navigation::HOTEL_ROLES);
        $router->post('/hotel/quartos/{id}/iniciar-limpeza', $hotel->startCleaning(...), Navigation::HOTEL_ROLES);
        $router->post('/hotel/quartos/{id}/marcar-limpo', $hotel->markClean(...), Navigation::HOTEL_ROLES);
        $router->post('/hotel/quartos/{id}/liberar', $hotel->releaseRoom(...), Navigation::HOTEL_ROLES);
        $router->post('/hotel/reservas/{id}/late-checkout', $hotel->updateLateCheckout(...), Navigation::HOTEL_ROLES);
        $router->get('/hotel/solicitacoes', $hotel->showRequests(...), Navigation::HOTEL_ROLES);
        $router->post('/hotel/solicitacoes/{id}/aprovar', $hotel->approveRequest(...), Navigation::HOTEL_ROLES);
        $router->post('/hotel/solicitacoes/{id}/recusar', $hotel->rejectRequest(...), Navigation::HOTEL_ROLES);

        $router->get('/hospede/reserva', $guest->showReservation(...), Navigation::GUEST_ROLES);
        $router->get('/hospede/disponibilidade', $guest->showAvailability(...), Navigation::GUEST_ROLES);
        $router->post('/hospede/solicitacoes', $guest->createRequest(...), Navigation::GUEST_ROLES);

        $router->get('/api/versao', $live->showVersion(...), Navigation::SIGNED_IN_ROLES);
    }
}
