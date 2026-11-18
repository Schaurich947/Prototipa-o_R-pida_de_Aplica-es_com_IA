<?php

declare(strict_types=1);

namespace HotelFlex\Bootstrap;

use HotelFlex\Http\Navigation;
use HotelFlex\Http\Router;

final class FiscalRoutes
{
    public static function register(Router $router, Container $container): void
    {
        $billing = static fn (string $method) => static fn (...$args) => $container->fiscalControllers()->billingProfiles()->$method(...$args);
        $hotelFiscal = static fn (string $method) => static fn (...$args) => $container->fiscalControllers()->hotelFiscal()->$method(...$args);
        $invoices = static fn (string $method) => static fn (...$args) => $container->fiscalControllers()->invoices()->$method(...$args);

        $router->get('/faturamento', $billing('show'), Navigation::GUEST_ROLES);
        $router->post('/faturamento', $billing('create'), Navigation::GUEST_ROLES);
        $router->post('/faturamento/{id}/remover', $billing('archive'), Navigation::GUEST_ROLES);

        $router->get('/hotel/fiscal', $hotelFiscal('show'), Navigation::HOTEL_ROLES);
        $router->post('/hotel/fiscal', $hotelFiscal('save'), Navigation::HOTEL_ROLES);

        $router->get('/hotel/notas', $invoices('showHotelList'), Navigation::HOTEL_ROLES);
        $router->post('/hotel/notas/processar', $invoices('processQueue'), Navigation::HOTEL_ROLES);
        $router->post('/hotel/notas/{id}/cancelar', $invoices('cancel'), Navigation::HOTEL_ROLES);
        $router->post('/hotel/notas/{id}/reenviar', $invoices('requeue'), Navigation::HOTEL_ROLES);
        $router->get('/notas/{id}', $invoices('showDocument'), Navigation::SIGNED_IN_ROLES);
        $router->get('/notas/{id}/xml', $invoices('downloadXml'), Navigation::SIGNED_IN_ROLES);
        $router->get('/notas/{id}/pdf', static fn (...$args) => $container->fiscalControllers()->invoicePdfs()->download(...$args), Navigation::SIGNED_IN_ROLES);
    }
}
