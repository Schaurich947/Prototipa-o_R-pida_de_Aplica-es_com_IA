<?php

declare(strict_types=1);

namespace Comprova\Bootstrap;

use Comprova\Http\Response;
use Comprova\Http\Router;

final class Routes
{
    /** Controllers are created lazily, so each request only builds what its route needs. */
    public static function register(Router $router, Container $container): void
    {
        $auth = static fn (string $method) => static fn (...$args) => $container->authController()->$method(...$args);
        $payments = static fn (string $method) => static fn (...$args) => $container->paymentsController()->$method(...$args);

        $router->get('/', static fn () => Response::redirect(Router::HOME_PATH), false);
        $router->get('/login', $auth('showLogin'), false);
        $router->post('/login', $auth('login'), false);
        $router->post('/logout', $auth('logout'));

        $router->get('/comprovantes', $payments('showDashboard'));
        $router->get('/comprovantes/novo', $payments('showForm'));
        $router->post('/comprovantes', $payments('store'));
        $router->get('/comprovantes/{id}', $payments('showDetail'));
        $router->get('/comprovantes/{id}/arquivo', $payments('downloadReceipt'));
        $router->post('/envios/{id}/reenviar', $payments('retry'));
        $router->post('/envios/processar', $payments('processQueue'));
        $router->get('/integracoes', static fn () => $container->integrationsController()->show());

        $router->get('/api/versao', static fn () => $container->liveUpdateController()->showVersion());
        $router->post('/api/webhooks/pagamentos', static fn (...$args) => $container->webhookController()->receivePayment(...$args), false, false);
    }
}
