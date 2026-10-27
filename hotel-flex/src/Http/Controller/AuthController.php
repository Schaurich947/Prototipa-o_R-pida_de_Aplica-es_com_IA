<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Domain\Role;
use HotelFlex\Http\HttpRequest;
use HotelFlex\Http\Navigation;
use HotelFlex\Http\Response;
use HotelFlex\Http\Session;
use HotelFlex\Http\SessionUser;
use HotelFlex\Service\HotelQueryService;
use HotelFlex\View\View;

final class AuthController
{
    public function __construct(
        private readonly Session $session,
        private readonly HotelQueryService $queries,
        private readonly View $view,
    ) {
    }

    public function showLogin(): void
    {
        $user = $this->session->getUser();
        if ($user) {
            Response::redirect(Navigation::getHomePath($user->role));
        }
        $this->view->render('login', ['reservations' => $this->queries->getReservations()], 'base');
    }

    public function login(HttpRequest $request): never
    {
        $role = Role::tryFrom($request->getBodyString('role'));
        if ($role === null) {
            $this->failLogin('Selecione um perfil válido.');
        }
        $reservationId = $role === Role::Guest ? $this->resolveGuestReservation($request) : null;
        $this->session->signIn(new SessionUser($role, $reservationId));
        Response::redirect(Navigation::getHomePath($role));
    }

    public function logout(): never
    {
        $this->session->signOut();
        Response::redirect('/login');
    }

    private function resolveGuestReservation(HttpRequest $request): int
    {
        $reservationId = filter_var($request->body['reservation_id'] ?? null, FILTER_VALIDATE_INT);
        $reservation = is_int($reservationId) ? $this->queries->findReservation($reservationId) : null;
        if ($reservation === null) {
            $this->failLogin('Selecione uma reserva válida para entrar como hóspede.');
        }

        return $reservation->id;
    }

    private function failLogin(string $message): never
    {
        $this->session->flash('error', $message);
        Response::redirect('/login');
    }
}
