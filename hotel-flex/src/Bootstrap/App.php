<?php

declare(strict_types=1);

namespace HotelFlex\Bootstrap;

use HotelFlex\Config\Env;
use HotelFlex\Database\ConnectionFactory;
use HotelFlex\Http\Auth;
use HotelFlex\Http\HttpRequest;
use HotelFlex\Http\Response;
use HotelFlex\Http\Router;
use HotelFlex\Http\Session;
use HotelFlex\Http\Url;
use HotelFlex\View\View;

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
        $container = new Container(ConnectionFactory::createAppConnection($env), $session, $view);
        $auth = $container->auth();

        $request = HttpRequest::fromGlobals();
        $view->share('currentUser', $auth->user());
        $view->share('csrfToken', $session->getCsrfToken());
        $view->share('flashes', $session->pullFlashes());
        $view->share('currentPath', $request->path);

        $router = new Router($session, $auth);
        Routes::register($router, $container);
        if (!$router->dispatch($request)) {
            ErrorHandler::renderNotFound($view);
        }
    }
}
