<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

/** The sale an invoice belongs to. */
enum InvoiceSource: string
{
    case Reservation = 'reserva';
    case Breakfast = 'cafe';
}
