<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

use DateTimeImmutable;

/** Check-in and check-out dates picked by the guest; the number of nights is derived from them. */
final readonly class StayDates
{
    public const MAX_NIGHTS = 30;

    public int $nights;

    public function __construct(
        public DateTimeImmutable $checkInDate,
        public DateTimeImmutable $checkOutDate,
    ) {
        $this->nights = (int) $checkInDate->setTime(0, 0)->diff($checkOutDate->setTime(0, 0))->format('%r%a');
    }

    public function assertValid(DateTimeImmutable $today): void
    {
        if ($this->checkInDate < $today->setTime(0, 0)) {
            throw new DomainException('A data de entrada não pode estar no passado.');
        }
        if ($this->nights < 1) {
            throw new DomainException('A data de saída deve ser depois da data de entrada.');
        }
        if ($this->nights > self::MAX_NIGHTS) {
            throw new DomainException('Reservas de até ' . self::MAX_NIGHTS . ' diárias.');
        }
    }
}
