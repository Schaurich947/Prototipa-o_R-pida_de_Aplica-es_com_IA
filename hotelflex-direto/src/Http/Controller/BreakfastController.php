<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Domain\BreakfastBooking;
use HotelFlex\Domain\BreakfastPaymentOption;
use HotelFlex\Domain\Clock;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\PaymentMethod;
use HotelFlex\Domain\Role;
use HotelFlex\Http\Auth;
use HotelFlex\Http\ContextSelector;
use HotelFlex\Http\HttpRequest;
use HotelFlex\Repository\BreakfastRepository;
use HotelFlex\Repository\HotelRepository;
use HotelFlex\Repository\UserRepository;
use HotelFlex\Service\BreakfastService;
use HotelFlex\Service\ReceiptStorage;
use HotelFlex\View\View;

/** Breakfast for anyone (customer screens) and the hotel's daily breakfast list (reception screens). */
final class BreakfastController
{
    private const HOTEL_PATH = '/hotel/cafe';

    public function __construct(
        private readonly Auth $auth,
        private readonly ContextSelector $context,
        private readonly BreakfastService $breakfast,
        private readonly BreakfastRepository $bookings,
        private readonly HotelRepository $hotels,
        private readonly UserRepository $users,
        private readonly ReceiptStorage $receipts,
        private readonly ActionRunner $actions,
        private readonly View $view,
        private readonly Clock $clock,
    ) {
    }

    public function showCustomerPage(HttpRequest $request): void
    {
        $actor = $this->auth->requireUser();
        $hotels = array_values(array_filter($this->hotels->findAll(), static fn ($hotel) => $hotel->sellsBreakfast()));
        $hotel = $request->getQueryInt('hotel') ? $this->breakfast->getHotelSellingBreakfast((int) $request->getQueryInt('hotel')) : null;
        $date = HttpRequest::parseDate($request->getQueryString('data'));

        $this->view->render('guest/breakfast', [
            'pageTitle' => 'Café da manhã',
            'hotels' => $hotels,
            'selectedHotel' => $hotel,
            'selectedDate' => $date,
            'people' => max(1, min(BreakfastService::MAX_PEOPLE, $request->getQueryInt('pessoas') ?? 1)),
            'seatsLeft' => $hotel && $date ? $this->breakfast->getAvailability($hotel, $date)->freeSeats : null,
            'myBookings' => $this->bookings->findByUser($actor->id),
            'hotelsById' => array_column(array_map(static fn ($item) => ['id' => $item->id, 'hotel' => $item], $this->hotels->findAll()), 'hotel', 'id'),
            'minDate' => $this->clock->now()->format('Y-m-d'),
        ]);
    }

    public function book(HttpRequest $request): never
    {
        $actor = $this->auth->requireUser();
        $this->actions->run(function () use ($actor, $request): string {
            $option = BreakfastPaymentOption::tryFrom($request->getBodyString('payment_option')) ?? throw new DomainException('Escolha a forma de pagamento.');
            $bookingId = $this->breakfast->book($actor, $request->requireBodyInt('hotel_id', 1, PHP_INT_MAX), $request->requireBodyDate('date'), $request->requireBodyInt('people', 1, BreakfastService::MAX_PEOPLE), $option);

            return '/cafe/' . $bookingId;
        }, 'Café da manhã reservado!', '/cafe');
    }

    /** @param array<string, int> $params */
    public function showBooking(HttpRequest $request, array $params): void
    {
        $booking = $this->breakfast->getOwnBooking($this->auth->requireUser(), $params['id']);
        $this->view->render('guest/breakfast-detail', [
            'pageTitle' => "Café {$booking->code}",
            'booking' => $booking,
            'hotel' => $this->breakfast->getHotelSellingBreakfast($booking->hotelId),
            'canCancel' => $booking->isCancellableBy($this->clock->now(), $this->breakfast->getHotelSellingBreakfast($booking->hotelId)),
        ]);
    }

    /** @param array<string, int> $params */
    public function submitReceipt(HttpRequest $request, array $params): never
    {
        $actor = $this->auth->requireUser();
        $file = is_array($request->files['receipt'] ?? null) ? $request->files['receipt'] : [];
        $this->actions->run(fn () => $this->breakfast->submitReceipt($actor, $params['id'], $file), 'Comprovante enviado. O hotel vai conferir.', "/cafe/{$params['id']}");
    }

    /** @param array<string, int> $params */
    public function cancel(HttpRequest $request, array $params): never
    {
        $actor = $this->auth->requireUser();
        $this->actions->run(fn () => $this->breakfast->cancel($actor, $params['id']), 'Café da manhã cancelado.', "/cafe/{$params['id']}");
    }

    public function showHotelPage(HttpRequest $request): void
    {
        $actor = $this->auth->requireUser();
        $hotel = $this->context->resolveHotel($actor, $request);
        $date = HttpRequest::parseDate($request->getQueryString('data')) ?? $this->clock->now()->setTime(0, 0);
        $this->breakfast->expireOverdue();
        $bookings = $this->bookings->findByHotelAndDate($hotel->id, $date);

        $this->view->render('hotel/breakfast', [
            'pageTitle' => 'Café da manhã',
            'hotel' => $hotel,
            'hotelOptions' => $this->context->getHotelOptions($actor),
            'date' => $date,
            'bookings' => $bookings,
            'customerNames' => $this->getCustomerNames($bookings),
            'availability' => $this->breakfast->getAvailability($hotel, $date),
            'isToday' => $date->format('Y-m-d') === $this->clock->now()->format('Y-m-d'),
            'hasEndedToday' => $this->clock->now() >= $this->clock->now()->setTime($hotel->breakfastEndHour, 0),
        ]);
    }

    public function sellWalkIn(HttpRequest $request): never
    {
        $actor = $this->auth->requireUser();
        $this->actions->run(function () use ($actor, $request): void {
            $method = PaymentMethod::tryFrom($request->getBodyString('method')) ?? throw new DomainException('Escolha como foi pago.');
            $hotelId = $request->requireBodyInt('hotel_id', 1, PHP_INT_MAX);
            $this->breakfast->sellWalkIn($actor, $hotelId, $request->requireBodyInt('people', 1, BreakfastService::MAX_PEOPLE), $method, $request->getBodyString('customer_name'));
        }, 'Venda na hora registrada.', self::HOTEL_PATH);
    }

    /** @param array<string, int> $params */
    public function hotelAction(HttpRequest $request, array $params): never
    {
        $actor = $this->auth->requireUser();
        $returnTo = self::HOTEL_PATH . '?data=' . rawurlencode($request->getBodyString('date'));
        $action = $request->getBodyString('action');
        $this->actions->run(fn () => match ($action) {
            'approve' => $this->breakfast->review($actor, $params['id'], true, ''),
            'reject' => $this->breakfast->review($actor, $params['id'], false, $request->getBodyString('reason')),
            'receive' => $this->breakfast->receiveAtDesk($actor, $params['id'], PaymentMethod::tryFrom($request->getBodyString('method')) ?? throw new DomainException('Escolha como foi pago.')),
            'served' => $this->breakfast->markServed($actor, $params['id']),
            default => throw new DomainException('Ação inválida.'),
        }, 'Café atualizado.', $returnTo);
    }

    /** @param array<string, int> $params */
    public function downloadReceipt(HttpRequest $request, array $params): never
    {
        $actor = $this->auth->requireUser();
        $booking = $actor->role === Role::Staff ? $this->breakfast->getManagedBooking($actor, $params['id']) : $this->breakfast->getOwnBooking($actor, $params['id']);
        if ($booking->receiptPath === null) {
            throw new DomainException('Este café não tem comprovante.');
        }
        ReceiptDownload::send($this->receipts->getAbsolutePath($booking->receiptPath), (string) $booking->receiptOriginalName, (string) $booking->receiptMime);
    }

    /** @param list<BreakfastBooking> $bookings @return array<int, string> user id => name */
    private function getCustomerNames(array $bookings): array
    {
        $names = [];
        foreach ($bookings as $booking) {
            $names[$booking->userId] ??= $this->users->findById($booking->userId)?->name ?? 'Cliente';
        }

        return $names;
    }
}
