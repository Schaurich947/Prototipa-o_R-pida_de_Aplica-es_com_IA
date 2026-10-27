<?php

declare(strict_types=1);

namespace HotelFlex\Http;

use HotelFlex\Domain\Role;

/** Single source for menu items and the roles allowed on each screen. */
final class Navigation
{
    public const HOTEL_ROLES = [Role::Staff, Role::Admin];
    public const GUEST_ROLES = [Role::Guest, Role::Admin];
    public const SIGNED_IN_ROLES = [Role::Staff, Role::Guest, Role::Admin];

    private const ITEMS = [
        ['path' => '/hotel/liberacao', 'label' => 'Liberação e horas', 'area' => 'Hotel', 'roles' => self::HOTEL_ROLES],
        ['path' => '/hotel/solicitacoes', 'label' => 'Solicitações', 'area' => 'Hotel', 'roles' => self::HOTEL_ROLES],
        ['path' => '/hospede/reserva', 'label' => 'Minha reserva', 'area' => 'Hóspede', 'roles' => self::GUEST_ROLES],
        ['path' => '/hospede/disponibilidade', 'label' => 'Disponibilidade', 'area' => 'Hóspede', 'roles' => self::GUEST_ROLES],
    ];

    /** @return array<string, list<array{path: string, label: string}>> items grouped by area */
    public static function getItemsByArea(Role $role): array
    {
        $groups = [];
        foreach (self::ITEMS as $item) {
            if (in_array($role, $item['roles'], true)) {
                $groups[$item['area']][] = ['path' => $item['path'], 'label' => $item['label']];
            }
        }

        return $groups;
    }

    public static function getHomePath(Role $role): string
    {
        $firstArea = array_values(self::getItemsByArea($role))[0] ?? [];

        return $firstArea[0]['path'] ?? '/login';
    }
}
