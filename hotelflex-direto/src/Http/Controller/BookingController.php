<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Domain\CancellationPolicy;
use HotelFlex\Domain\Clock;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\PaymentPlan;
use HotelFlex\Domain\StayDates;
use HotelFlex\Http\Auth;
use HotelFlex\Http\ContextSelector;
use HotelFlex\Http\HttpRequest;
use HotelFlex\Service\BookingSearch;
use HotelFlex\Service\OfferFilter;
use HotelFlex\Service\OfferSort;
use HotelFlex\Service\ReservationService;
use HotelFlex\View\View;

/** Search → review → confirm. Nothing is saved until the guest confirms on the review screen. */
final class BookingController
{
    private const SEARCH_PATH = '/hospede/nova-reserva';

    public function __construct(
        private readonly Auth $auth,
        private readonly ContextSelector $context,
        private readonly BookingSearch $search,
        private readonly ReservationService $reservationService,
        private readonly ActionRunner $actions,
        private readonly View $view,
        private readonly Clock $clock,
    ) {
    }

    public function showSearch(HttpRequest $request): void
    {
        $actor = $this->auth->requireUser();
        $dates = $this->readDates($request->getQueryString('entrada'), $request->getQueryString('saida'));
        $dateError = $this->validateDates($dates);
        $allOffers = $dates !== null && $dateError === null ? $this->search->search($dates, $request->getQueryString('cidade')) : null;
        $filter = new OfferFilter($request->getQueryString('categoria'), self::readMaxPrice($request->getQueryString('preco_max')), OfferSort::tryFrom($request->getQueryString('ordem')) ?? OfferSort::PriceAsc);

        $this->view->render('guest/new-reservation', [
            'pageTitle' => 'Nova reserva',
            'guest' => $this->context->resolveGuest($actor, $request),
            'guestOptions' => $this->context->getGuestOptions($actor),
            'cities' => $this->search->getCities(),
            'categories' => $allOffers !== null ? OfferFilter::getCategories($allOffers) : [],
            'query' => $request->query,
            'dates' => $dates,
            'dateError' => $request->getQueryString('entrada') !== '' ? $dateError : null,
            'filter' => $filter,
            'offers' => $allOffers !== null ? $filter->apply($allOffers) : null,
            'minDate' => $this->clock->now()->format('Y-m-d'),
        ]);
    }

    public function showReview(HttpRequest $request): void
    {
        $actor = $this->auth->requireUser();
        $guest = $this->context->resolveGuest($actor, $request);
        $dates = $this->readDates($request->getQueryString('entrada'), $request->getQueryString('saida')) ?? throw new DomainException('Datas inválidas.');
        $dates->assertValid($this->clock->now());
        $stay = $this->search->buildPreview($guest, $request->getQueryInt('quarto') ?? 0, $dates);

        $this->view->render('guest/review-reservation', [
            'pageTitle' => 'Revisar reserva',
            'stay' => $stay,
            'dates' => $dates,
            'guest' => $guest,
            'freeCancellationUntil' => CancellationPolicy::getFreeCancellationDeadline($stay->hotel, $stay->reservation),
            'paymentDeadline' => $this->clock->now()->modify("+{$stay->hotel->paymentDeadlineHours} hours"),
            'backUrl' => url(self::SEARCH_PATH . '?' . http_build_query(['entrada' => $dates->checkInDate->format('Y-m-d'), 'saida' => $dates->checkOutDate->format('Y-m-d')])),
        ]);
    }

    public function book(HttpRequest $request): never
    {
        $actor = $this->auth->requireUser();
        $guest = $this->context->resolveGuest($actor, $request);
        $backToReview = self::SEARCH_PATH . '/revisar?' . http_build_query([
            'quarto' => $request->getBodyString('room_id'), 'entrada' => $request->getBodyString('check_in_date'), 'saida' => $request->getBodyString('check_out_date'),
        ]);
        $this->actions->run(function () use ($guest, $request): string {
            $dates = new StayDates($request->requireBodyDate('check_in_date'), $request->requireBodyDate('check_out_date'));
            $plan = PaymentPlan::tryFrom($request->getBodyString('payment_plan')) ?? throw new DomainException('Escolha a forma de pagamento.');
            $reservationId = $this->reservationService->book($guest, $request->requireBodyInt('room_id', 1, PHP_INT_MAX), $dates, $plan, ($request->body['accept_policy'] ?? '') === '1');

            return '/hospede/reservas/' . $reservationId;
        }, 'Reserva criada! Pague via Pix e envie o comprovante para confirmar.', $backToReview);
    }

    private function readDates(string $checkIn, string $checkOut): ?StayDates
    {
        $checkInDate = HttpRequest::parseDate($checkIn);
        $checkOutDate = HttpRequest::parseDate($checkOut);

        return $checkInDate && $checkOutDate ? new StayDates($checkInDate, $checkOutDate) : null;
    }

    private function validateDates(?StayDates $dates): ?string
    {
        if ($dates === null) {
            return 'Informe as datas de entrada e de saída.';
        }
        try {
            $dates->assertValid($this->clock->now());

            return null;
        } catch (DomainException $error) {
            return $error->getMessage();
        }
    }

    /** "450" or "450,00" reais per night → cents; empty means no limit. */
    private static function readMaxPrice(string $value): ?int
    {
        $reais = filter_var(str_replace(',', '.', $value), FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 1]]);

        return $reais === false ? null : (int) round($reais * 100);
    }
}
