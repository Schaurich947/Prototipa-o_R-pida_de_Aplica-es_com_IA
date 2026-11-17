<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Repository;

use DateTimeImmutable;
use HotelFlex\Fiscal\Domain\BillingAddress;
use HotelFlex\Fiscal\Domain\BillingKind;
use HotelFlex\Fiscal\Domain\BillingProfile;
use HotelFlex\Fiscal\Domain\CompanyRecord;
use HotelFlex\Fiscal\Domain\TaxDocument;
use PDO;

final class BillingProfileRepository
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';
    private const EMPTY_ADDRESS = ['street' => null, 'street_number' => null, 'district' => null, 'city' => null, 'state' => null, 'postal_code' => null, 'municipality_code' => null];

    public function __construct(private readonly PDO $connection)
    {
    }

    public function create(int $userId, BillingKind $kind, string $name, TaxDocument $document, ?string $email, ?BillingAddress $address, string $country, ?CompanyRecord $company, DateTimeImmutable $now): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO billing_profiles (user_id, kind, name, document, email, country, street, street_number, district, city, state, postal_code, municipality_code,
                company_status, company_is_simples, company_is_mei, lookup_source, looked_up_at, created_at)
             VALUES (:user_id, :kind, :name, :document, :email, :country, :street, :street_number, :district, :city, :state, :postal_code, :municipality_code,
                :company_status, :company_is_simples, :company_is_mei, :lookup_source, :looked_up_at, :created_at)',
        );
        $statement->execute([
            'user_id' => $userId, 'kind' => $kind->value, 'name' => $name, 'document' => $document->value, 'email' => $email, 'country' => $country,
            ...($address?->toRow() ?? self::EMPTY_ADDRESS),
            ...self::toCompanyColumns($company),
            'created_at' => $now->format(self::DATE_FORMAT),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /** @return list<BillingProfile> */
    public function findActiveByUser(int $userId): array
    {
        $statement = $this->connection->prepare('SELECT * FROM billing_profiles WHERE user_id = :user_id AND archived_at IS NULL ORDER BY id');
        $statement->execute(['user_id' => $userId]);

        return array_map(BillingProfile::fromRow(...), $statement->fetchAll());
    }

    /** Archived profiles are still found: a reservation made with one keeps its invoice data. */
    public function findById(int $profileId): ?BillingProfile
    {
        $statement = $this->connection->prepare('SELECT * FROM billing_profiles WHERE id = :id');
        $statement->execute(['id' => $profileId]);
        $row = $statement->fetch();

        return $row ? BillingProfile::fromRow($row) : null;
    }

    public function archive(int $profileId, int $userId, DateTimeImmutable $now): bool
    {
        $statement = $this->connection->prepare('UPDATE billing_profiles SET archived_at = :now WHERE id = :id AND user_id = :user_id AND archived_at IS NULL');
        $statement->execute(['now' => $now->format(self::DATE_FORMAT), 'id' => $profileId, 'user_id' => $userId]);

        return $statement->rowCount() === 1;
    }

    /** @return array<string, mixed> */
    private static function toCompanyColumns(?CompanyRecord $company): array
    {
        return [
            'company_status' => $company?->status,
            'company_is_simples' => $company?->isSimples === null ? null : (int) $company->isSimples,
            'company_is_mei' => $company?->isMei === null ? null : (int) $company->isMei,
            'lookup_source' => $company?->source,
            'looked_up_at' => $company?->lookedUpAt->format(self::DATE_FORMAT),
        ];
    }
}
