<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Domain\ReservationStatus;
use HotelFlex\Domain\User;
use HotelFlex\Repository\ReservationRepository;
use HotelFlex\Repository\ServiceRequestRepository;

/** All reservations of a guest across every hotel of the platform. */
final class GuestReservationsQuery
{
    public function __construct(
        private readonly AccessGuard $guard,
        private readonly StayViewFactory $stays,
        private readonly ServiceRequestRepository $requests,
        private readonly ReservationRepository $reservations,
    ) {
    }

    /** @return array{current: list<StayView>, upcoming: list<StayView>, history: list<StayView>} */
    public function getGroupedStays(User $guest): array
    {
        $groups = ['current' => [], 'upcoming' => [], 'history' => []];
        foreach ($this->stays->buildMany($this->reservations->findByUser($guest->id)) as $stay) {
            $group = match ($stay->reservation->status) {
                ReservationStatus::CheckedIn => 'current',
                ReservationStatus::Confirmed => 'upcoming',
                ReservationStatus::CheckedOut, ReservationStatus::Cancelled => 'history',
            };
            $groups[$group][] = $stay;
        }
        $groups['upcoming'] = array_reverse($groups['upcoming']);

        return $groups;
    }

    public function getStay(User $actor, int $reservationId): StayView
    {
        return $this->stays->build($this->guard->getOwnReservation($actor, $reservationId));
    }

    /** @return list<RequestView> */
    public function getRequestViews(StayView $stay): array
    {
        return array_map(
            static fn ($request) => new RequestView($request, $stay),
            $this->requests->findByReservationId($stay->reservation->id),
        );
    }
}
