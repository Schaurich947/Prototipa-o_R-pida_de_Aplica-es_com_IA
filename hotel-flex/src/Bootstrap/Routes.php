<?php

declare(strict_types=1);

namespace HotelFlex\Bootstrap;

use HotelFlex\Http\Navigation;
use HotelFlex\Http\Response;
use HotelFlex\Http\Router;

final class Routes
{
    /** Controllers are created lazily, so each request only builds what its route needs. */
    public static function register(Router $router, Container $container): void
    {
        $auth = static fn (string $method) => static fn (...$args) => $container->authController()->$method(...$args);
        $panel = static fn (string $method) => static fn (...$args) => $container->hotelPanelController()->$method(...$args);
        $requests = static fn (string $method) => static fn (...$args) => $container->hotelRequestsController()->$method(...$args);
        $guest = static fn (string $method) => static fn (...$args) => $container->guestReservationsController()->$method(...$args);
        $booking = static fn (string $method) => static fn (...$args) => $container->bookingController()->$method(...$args);
        $hotelRoles = Navigation::HOTEL_ROLES;
        $guestRoles = Navigation::GUEST_ROLES;

        $router->get('/', static fn () => Response::redirect('/login'), null);
        $router->get('/login', $auth('showLogin'), null);
        $router->post('/login', $auth('login'), null);
        $router->get('/cadastro', $auth('showRegister'), null);
        $router->post('/cadastro', $auth('register'), null);
        $router->post('/logout', $auth('logout'), Navigation::SIGNED_IN_ROLES);

        $router->get('/hotel/liberacao', $panel('showPanel'), $hotelRoles);
        $router->post('/hotel/quartos/{id}/iniciar-limpeza', $panel('startCleaning'), $hotelRoles);
        $router->post('/hotel/quartos/{id}/marcar-limpo', $panel('markClean'), $hotelRoles);
        $router->post('/hotel/quartos/{id}/liberar', $panel('releaseRoom'), $hotelRoles);
        $router->post('/hotel/reservas/{id}/check-in', $panel('checkIn'), $hotelRoles);
        $router->post('/hotel/reservas/{id}/check-out', $panel('checkOut'), $hotelRoles);
        $router->post('/hotel/reservas/{id}/horarios', $panel('updateHours'), $hotelRoles);
        $router->get('/hotel/solicitacoes', $requests('showRequests'), $hotelRoles);
        $router->post('/hotel/solicitacoes/{id}/aprovar', $requests('approveRequest'), $hotelRoles);
        $router->post('/hotel/solicitacoes/{id}/recusar', $requests('rejectRequest'), $hotelRoles);

        $router->get('/hospede/reservas', $guest('showList'), $guestRoles);
        $router->get('/hospede/reservas/{id}', $guest('showDetail'), $guestRoles);
        $router->post('/hospede/reservas/{id}/horarios', $guest('requestHours'), $guestRoles);
        $router->post('/hospede/reservas/{id}/datas', $guest('changeDates'), $guestRoles);
        $router->post('/hospede/reservas/{id}/cancelar', $guest('cancel'), $guestRoles);
        $router->get('/hospede/nova-reserva', $booking('showSearch'), $guestRoles);
        $router->post('/hospede/nova-reserva', $booking('book'), $guestRoles);

        $router->get('/api/versao', static fn () => $container->liveUpdateController()->showVersion(), Navigation::SIGNED_IN_ROLES);
    }
}
