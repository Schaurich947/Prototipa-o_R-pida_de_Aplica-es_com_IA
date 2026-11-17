<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Repository;

use DateTimeImmutable;
use PDO;

/** Stores CNPJ lookups (public company data) so the same company is not queried on every booking. */
final class CompanyLookupCache
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return array<string, mixed>|null */
    public function find(string $cnpj, DateTimeImmutable $notBefore): ?array
    {
        $statement = $this->connection->prepare('SELECT payload FROM company_lookups WHERE cnpj = :cnpj AND looked_up_at >= :not_before');
        $statement->execute(['cnpj' => $cnpj, 'not_before' => $notBefore->format('Y-m-d H:i:s')]);
        $payload = $statement->fetchColumn();

        return is_string($payload) ? json_decode($payload, true, 8, JSON_THROW_ON_ERROR) : null;
    }

    /** @param array<string, mixed> $payload */
    public function save(string $cnpj, array $payload, DateTimeImmutable $now): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO company_lookups (cnpj, payload, looked_up_at) VALUES (:cnpj, :payload, :now) ON DUPLICATE KEY UPDATE payload = VALUES(payload), looked_up_at = VALUES(looked_up_at)',
        );
        $statement->execute(['cnpj' => $cnpj, 'payload' => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'now' => $now->format('Y-m-d H:i:s')]);
    }
}
