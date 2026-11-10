<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Http\Auth;
use HotelFlex\Http\HttpRequest;
use HotelFlex\Service\AuthService;
use HotelFlex\View\View;

/** Contact preferences: WhatsApp number and consent for automatic messages. */
final class ProfileController
{
    public function __construct(
        private readonly Auth $auth,
        private readonly AuthService $authService,
        private readonly ActionRunner $actions,
        private readonly View $view,
    ) {
    }

    public function show(): void
    {
        $this->view->render('profile', ['pageTitle' => 'Meu perfil', 'user' => $this->auth->requireUser()]);
    }

    public function update(HttpRequest $request): never
    {
        $user = $this->auth->requireUser();
        $this->actions->run(
            fn () => $this->authService->updateWhatsAppContact($user, $request->getBodyString('whatsapp_phone'), ($request->body['whatsapp_consent'] ?? '') === '1'),
            'Preferências de contato atualizadas.',
            '/perfil',
            $request->body,
        );
    }
}
