<?php

declare(strict_types=1);

namespace HotelFlex\Bootstrap;

use HotelFlex\View\View;
use Throwable;

/** The real error goes only to the server log; the browser gets a generic page. */
final class ErrorHandler
{
    public static function register(View $view, bool $isDebug): void
    {
        ini_set('display_errors', '0');
        set_exception_handler(static function (Throwable $error) use ($view, $isDebug): void {
            error_log(sprintf('[hotelflex] %s: %s em %s:%d', $error::class, $error->getMessage(), $error->getFile(), $error->getLine()));
            self::renderServerError($view, $isDebug ? $error->getMessage() : null);
        });
    }

    public static function renderNotFound(View $view): void
    {
        http_response_code(404);
        $view->render('errors/404', ['pageTitle' => 'Página não encontrada'], 'base');
    }

    private static function renderServerError(View $view, ?string $debugMessage): void
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(500);
        try {
            $view->render('errors/500', ['pageTitle' => 'Erro', 'debugMessage' => $debugMessage], 'base');
        } catch (Throwable $renderError) {
            error_log('[hotelflex] Falha ao renderizar página de erro: ' . $renderError->getMessage());
            echo 'Ocorreu um erro inesperado. Tente novamente mais tarde.';
        }
    }
}
