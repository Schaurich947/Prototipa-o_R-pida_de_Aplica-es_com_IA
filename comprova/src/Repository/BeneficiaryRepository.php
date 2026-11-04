<?php

declare(strict_types=1);

namespace Comprova\Repository;

use Comprova\Domain\Beneficiary;
use Comprova\Domain\PhoneNumber;
use DateTimeImmutable;
use PDO;

final class BeneficiaryRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findById(int $beneficiaryId): ?Beneficiary
    {
        $statement = $this->connection->prepare('SELECT * FROM beneficiaries WHERE id = :id');
        $statement->execute(['id' => $beneficiaryId]);
        $row = $statement->fetch();

        return $row ? Beneficiary::fromRow($row) : null;
    }

    /**
     * Creates the beneficiary or updates name/phone. Consent is only recorded when given now,
     * and removing the phone also removes the consent.
     */
    public function save(string $name, string $email, ?PhoneNumber $phone, ?DateTimeImmutable $optInAt): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO beneficiaries (name, email, whatsapp_phone, whatsapp_opt_in_at)
             VALUES (:name, :email, :phone, :opt_in_at)
             ON DUPLICATE KEY UPDATE
                 id = LAST_INSERT_ID(id),
                 name = VALUES(name),
                 whatsapp_phone = VALUES(whatsapp_phone),
                 whatsapp_opt_in_at = IF(VALUES(whatsapp_phone) IS NULL, NULL, COALESCE(VALUES(whatsapp_opt_in_at), whatsapp_opt_in_at))',
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone?->e164Digits,
            'opt_in_at' => $optInAt?->format('Y-m-d H:i:s'),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /** @param list<int> $ids @return array<int, Beneficiary> indexed by id */
    public function findByIds(array $ids): array
    {
        $uniqueIds = array_values(array_unique($ids));
        if ($uniqueIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($uniqueIds), '?'));
        $statement = $this->connection->prepare("SELECT * FROM beneficiaries WHERE id IN ({$placeholders})");
        $statement->execute($uniqueIds);
        $indexed = [];
        foreach ($statement->fetchAll() as $row) {
            $indexed[(int) $row['id']] = Beneficiary::fromRow($row);
        }

        return $indexed;
    }
}
