<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Http\Auth;
use HotelFlex\Http\ContextSelector;
use HotelFlex\Http\HttpRequest;
use HotelFlex\Service\HotelPanelQuery;
use HotelFlex\Service\RequestService;
use HotelFlex\View\View;

final class HotelRequestsController
{
    private const REQUESTS_PATH = '/hotel/solicitacoes';

    public function __construct(
        private readonly Auth $auth,
        private readonly ContextSelector $context,
        private readonly RequestService $requestService,
        private readonly HotelPanelQuery $queries,
        private readonly ActionRunner $actions,
        private readonly View $view,
    ) {
    }

    public function showRequests(HttpRequest $request): void
    {
        $actor = $this->auth->requireUser();
        $hotel = $this->context->resolveHotel($actor, $request);
        $requestViews = $this->queries->getRequestViews($hotel);

        $this->view->render('hotel/requests', [
            'pageTitle' => 'Solicitações',
            'hotel' => $hotel,
            'hotelOptions' => $this->context->getHotelOptions($actor),
            'pendingViews' => array_values(array_filter($requestViews, static fn ($view) => $view->request->isPending())),
            'resolvedViews' => array_values(array_filter($requestViews, static fn ($view) => !$view->request->isPending())),
        ]);
    }

    /** @param array<string, int> $params */
    public function approveRequest(HttpRequest $request, array $params): never
    {
        $actor = $this->auth->requireUser();
        $this->actions->run(fn () => $this->requestService->approveRequest($actor, $params['id']), 'Solicitação aprovada.', self::REQUESTS_PATH);
    }

    /** @param array<string, int> $params */
    public function rejectRequest(HttpRequest $request, array $params): never
    {
        $actor = $this->auth->requireUser();
        $this->actions->run(fn () => $this->requestService->rejectRequest($actor, $params['id']), 'Solicitação recusada.', self::REQUESTS_PATH);
    }
}
