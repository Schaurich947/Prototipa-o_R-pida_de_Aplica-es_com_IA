<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Domain\Role;
use HotelFlex\Fiscal\Service\HotelFiscalService;
use HotelFlex\Http\Auth;
use HotelFlex\Http\ContextSelector;
use HotelFlex\Http\HttpRequest;
use HotelFlex\Http\Session;
use HotelFlex\View\View;

/** The hotel as invoice issuer: CNPJ, registrations, regime and tax rates. */
final class HotelFiscalController
{
    private const PATH = '/hotel/fiscal';
    private const FORM_FIELDS = ['legal_name', 'cnpj', 'municipal_registration', 'state_registration', 'tax_regime', 'municipality_code', 'address', 'service_code', 'iss_rate', 'icms_rate', 'series', 'iss_withholding', 'allows_anonymous_consumer'];

    public function __construct(
        private readonly Auth $auth,
        private readonly Session $session,
        private readonly ContextSelector $context,
        private readonly HotelFiscalService $fiscal,
        private readonly ActionRunner $actions,
        private readonly View $view,
    ) {
    }

    public function show(HttpRequest $request): void
    {
        $actor = $this->auth->requireUser();
        $hotel = $this->context->resolveHotel($actor, $request);

        $this->view->render('hotel/fiscal-profile', [
            'pageTitle' => 'Dados fiscais',
            'hotel' => $hotel,
            'hotelOptions' => $this->context->getHotelOptions($actor),
            'profile' => $this->fiscal->find($hotel->id),
            'canEdit' => $actor->role === Role::Admin,
            'old' => $this->session->pullOldInput(),
        ]);
    }

    public function save(HttpRequest $request): never
    {
        $actor = $this->auth->requireUser();
        $hotelId = $request->requireBodyInt('hotel_id', 1, PHP_INT_MAX);
        $input = array_map(static fn (string $field) => $request->getBodyString($field), array_combine(self::FORM_FIELDS, self::FORM_FIELDS));
        $this->actions->run(fn () => $this->fiscal->save($actor, $hotelId, $input), 'Dados fiscais salvos.', self::PATH . "?hotel={$hotelId}", $input);
    }
}
