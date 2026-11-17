<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Repository;

use DateTimeImmutable;
use HotelFlex\Fiscal\Domain\ServiceType;
use HotelFlex\Fiscal\Domain\TaxRule;
use PDO;

final class TaxRuleRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return list<TaxRule> rules in force on the given date */
    public function findValid(ServiceType $serviceType, DateTimeImmutable $date): array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM tax_rules WHERE service_type = :service_type AND valid_from <= :day_from AND (valid_until IS NULL OR valid_until >= :day_until) ORDER BY tax',
        );
        $statement->execute(['service_type' => $serviceType->value, 'day_from' => $date->format('Y-m-d'), 'day_until' => $date->format('Y-m-d')]);

        return array_map(TaxRule::fromRow(...), $statement->fetchAll());
    }
}
