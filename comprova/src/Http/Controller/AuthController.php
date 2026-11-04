<?php

declare(strict_types=1);

namespace Comprova\Http\Controller;

use Comprova\Domain\DomainException;
use Comprova\Http\Auth;
use Comprova\Http\HttpRequest;
use Comprova\Http\Response;
use Comprova\Http\Router;
use Comprova\Http\Session;
use Comprova\Service\AuthService;
use Comprova\View\View;

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
        if ($this->auth->user() !== null) {
            Response::redirect(Router::HOME_PATH);
        }
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

            return Router::HOME_PATH;
        }, 'Bem-vindo!', '/login', $request->body);
    }

    public function logout(): never
    {
        $this->session->signOut();
        Response::redirect('/login');
    }
}
