<?php

declare(strict_types=1);

namespace Comprova\Bootstrap;

use Comprova\Config\Env;
use Comprova\Database\ConnectionFactory;
use Comprova\Http\HttpRequest;
use Comprova\Http\Response;
use Comprova\Http\Router;
use Comprova\Http\Session;
use Comprova\Http\Url;
use Comprova\View\View;

/** Entry point: environment, security headers, session, routing and error handling. */
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
        $container = new Container(ConnectionFactory::createAppConnection($env), $env, $session, $view, $projectRoot);

        $request = HttpRequest::fromGlobals();
        $view->share('currentUser', $container->auth()->user());
        $view->share('csrfToken', $session->getCsrfToken());
        $view->share('flashes', $session->pullFlashes());
        $view->share('currentPath', $request->path);
        $view->share('companyName', $env->get('COMPANY_NAME', 'Minha empresa'));

        $router = new Router($session, $container->auth());
        Routes::register($router, $container);
        if (!$router->dispatch($request)) {
            ErrorHandler::renderNotFound($view);
        }
    }
}
