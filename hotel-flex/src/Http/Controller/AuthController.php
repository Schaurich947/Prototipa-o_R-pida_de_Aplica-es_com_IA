<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Domain\DomainException;
use HotelFlex\Http\Auth;
use HotelFlex\Http\HttpRequest;
use HotelFlex\Http\Navigation;
use HotelFlex\Http\Response;
use HotelFlex\Http\Session;
use HotelFlex\Service\AuthService;
use HotelFlex\View\View;

final class AuthController
{
    public function __construct(
        private readonly Session $session,
        private readonly Auth $auth,
        private readonly AuthService $authService,
        private readonly ActionRunner $actions,
        private readonly View $view,
    ) {
    }

    public function showLogin(): void
    {
        $this->redirectIfSignedIn();
        $this->view->render('auth/login', ['pageTitle' => 'Entrar', 'old' => $this->session->pullOldInput()], 'base');
    }

    public function login(HttpRequest $request): never
    {
        $this->actions->run(function () use ($request): string {
            if ($this->session->isLoginLocked()) {
                throw new DomainException('Muitas tentativas. Aguarde alguns minutos e tente novamente.');
            }
            try {
                $user = $this->authService->authenticate($request->getBodyString('email'), (string) ($request->body['password'] ?? ''));
            } catch (DomainException $error) {
                $this->session->registerFailedLogin();
                throw $error;
            }
            $this->session->signIn($user->id);

            return Navigation::getHomePath($user->role);
        }, 'Bem-vindo!', '/login', $request->body);
    }

    public function showRegister(): void
    {
        $this->redirectIfSignedIn();
        $this->view->render('auth/register', ['pageTitle' => 'Criar conta', 'old' => $this->session->pullOldInput()], 'base');
    }

    public function register(HttpRequest $request): never
    {
        $this->actions->run(function () use ($request): string {
            $user = $this->authService->registerGuest(
                $request->getBodyString('name'),
                $request->getBodyString('email'),
                (string) ($request->body['password'] ?? ''),
                (string) ($request->body['password_confirmation'] ?? ''),
            );
            $this->session->signIn($user->id);

            return Navigation::getHomePath($user->role);
        }, 'Conta criada! Agora você já pode reservar.', '/cadastro', $request->body);
    }

    public function logout(): never
    {
        $this->session->signOut();
        Response::redirect('/login');
    }

    private function redirectIfSignedIn(): void
    {
        $user = $this->auth->user();
        if ($user !== null) {
            Response::redirect(Navigation::getHomePath($user->role));
        }
    }
}
