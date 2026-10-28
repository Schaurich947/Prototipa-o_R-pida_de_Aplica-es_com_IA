<?php

declare(strict_types=1);

namespace HotelFlex\Http;

use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\Hotel;
use HotelFlex\Domain\Role;
use HotelFlex\Domain\User;
use HotelFlex\Repository\HotelRepository;
use HotelFlex\Repository\UserRepository;

/**
 * Decides which hotel and which guest a screen shows. Staff are pinned to
 * their hotel and guests to themselves; only admins can switch (?hotel=, ?hospede=).
 */
final class ContextSelector
{
    public function __construct(
        private readonly Session $session,
        private readonly HotelRepository $hotels,
        private readonly UserRepository $users,
    ) {
    }

    public function resolveHotel(User $actor, HttpRequest $request): Hotel
    {
        if ($actor->role !== Role::Admin) {
            return $this->hotels->findById((int) $actor->hotelId) ?? throw new DomainException('Sua conta não está vinculada a um hotel.');
        }
        $hotelId = $request->getQueryInt('hotel') ?? $this->session->get('admin_hotel_id');
        $hotel = is_int($hotelId) ? $this->hotels->findById($hotelId) : null;
        $hotel ??= $this->hotels->findAll()[0] ?? throw new DomainException('Nenhum hotel cadastrado.');
        $this->session->set('admin_hotel_id', $hotel->id);

        return $hotel;
    }

    public function resolveGuest(User $actor, HttpRequest $request): User
    {
        if ($actor->role !== Role::Admin) {
            return $actor;
        }
        $guestId = $request->getQueryInt('hospede') ?? $this->session->get('admin_guest_id');
        $guest = is_int($guestId) ? $this->users->findById($guestId) : null;
        $guest = $guest?->role === Role::Guest ? $guest : ($this->users->findGuests()[0] ?? $actor);
        $this->session->set('admin_guest_id', $guest->id);

        return $guest;
    }

    /** @return list<Hotel> */
    public function getHotelOptions(User $actor): array
    {
        return $actor->role === Role::Admin ? $this->hotels->findAll() : [];
    }

    /** @return list<User> */
    public function getGuestOptions(User $actor): array
    {
        return $actor->role === Role::Admin ? $this->users->findGuests() : [];
    }
}
