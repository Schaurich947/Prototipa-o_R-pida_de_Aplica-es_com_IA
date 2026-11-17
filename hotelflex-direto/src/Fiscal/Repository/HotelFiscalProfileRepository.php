<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Repository;

use HotelFlex\Fiscal\Domain\HotelFiscalProfile;
use PDO;

final class HotelFiscalProfileRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findByHotel(int $hotelId): ?HotelFiscalProfile
    {
        $statement = $this->connection->prepare('SELECT * FROM hotel_fiscal_profiles WHERE hotel_id = :hotel_id');
        $statement->execute(['hotel_id' => $hotelId]);
        $row = $statement->fetch();

        return $row ? HotelFiscalProfile::fromRow($row) : null;
    }

    /** Saving never touches the invoice counter (next_number). */
    public function save(HotelFiscalProfile $profile): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO hotel_fiscal_profiles (hotel_id, legal_name, cnpj, municipal_registration, state_registration, tax_regime, municipality_code, address,
                service_code, iss_rate_bp, icms_rate_bp, series, iss_withholding)
             VALUES (:hotel_id, :legal_name, :cnpj, :municipal_registration, :state_registration, :tax_regime, :municipality_code, :address,
                :service_code, :iss_rate_bp, :icms_rate_bp, :series, :iss_withholding)
             ON DUPLICATE KEY UPDATE legal_name = VALUES(legal_name), cnpj = VALUES(cnpj), municipal_registration = VALUES(municipal_registration),
                state_registration = VALUES(state_registration), tax_regime = VALUES(tax_regime), municipality_code = VALUES(municipality_code),
                address = VALUES(address), service_code = VALUES(service_code), iss_rate_bp = VALUES(iss_rate_bp), icms_rate_bp = VALUES(icms_rate_bp),
                series = VALUES(series), iss_withholding = VALUES(iss_withholding)',
        );
        $statement->execute([
            'hotel_id' => $profile->hotelId, 'legal_name' => $profile->legalName, 'cnpj' => $profile->cnpj->value,
            'municipal_registration' => $profile->municipalRegistration, 'state_registration' => $profile->stateRegistration,
            'tax_regime' => $profile->taxRegime->value, 'municipality_code' => $profile->municipalityCode, 'address' => $profile->address,
            'service_code' => $profile->serviceCode, 'iss_rate_bp' => $profile->issRateBasisPoints, 'icms_rate_bp' => $profile->icmsRateBasisPoints,
            'series' => $profile->series, 'iss_withholding' => $profile->issWithholding->value,
        ]);
    }

    /** Must run inside a transaction: the row lock makes the numbering sequential with parallel workers. */
    public function reserveNextNumber(int $hotelId): int
    {
        $statement = $this->connection->prepare('SELECT next_number FROM hotel_fiscal_profiles WHERE hotel_id = :hotel_id FOR UPDATE');
        $statement->execute(['hotel_id' => $hotelId]);
        $number = (int) $statement->fetchColumn();
        $this->connection->prepare('UPDATE hotel_fiscal_profiles SET next_number = next_number + 1 WHERE hotel_id = :hotel_id')->execute(['hotel_id' => $hotelId]);

        return $number;
    }
}
