<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Service;

use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\Role;
use HotelFlex\Domain\User;
use HotelFlex\Fiscal\Domain\HotelFiscalProfile;
use HotelFlex\Fiscal\Domain\IssWithholdingRule;
use HotelFlex\Fiscal\Domain\TaxDocument;
use HotelFlex\Fiscal\Domain\TaxRegime;
use HotelFlex\Fiscal\Repository\HotelFiscalProfileRepository;
use HotelFlex\Repository\HotelRepository;

/** The hotel's issuer data. Only the admin changes it (least privilege); the reception can read it. */
final class HotelFiscalService
{
    /** LC 116/2003 and LC 157/2016: ISS between 2% and 5%. */
    private const MIN_ISS_BP = 200;
    private const MAX_ISS_BP = 500;
    private const MAX_ICMS_BP = 3500;

    public function __construct(
        private readonly HotelFiscalProfileRepository $profiles,
        private readonly HotelRepository $hotels,
    ) {
    }

    public function find(int $hotelId): ?HotelFiscalProfile
    {
        return $this->profiles->findByHotel($hotelId);
    }

    /** @param array<string, string> $input */
    public function save(User $actor, int $hotelId, array $input): void
    {
        if ($actor->role !== Role::Admin) {
            throw new DomainException('Só o administrador altera os dados fiscais do hotel.');
        }
        if ($this->hotels->findById($hotelId) === null) {
            throw new DomainException('Hotel não encontrado.');
        }
        $this->profiles->save(new HotelFiscalProfile(
            $hotelId,
            self::requireText($input['legal_name'] ?? '', 3, 150, 'Informe a razão social do hotel.'),
            TaxDocument::cnpj($input['cnpj'] ?? ''),
            self::requirePattern($input['municipal_registration'] ?? '', '/^[0-9A-Za-z.\-\/]{1,20}$/', 'Informe a inscrição municipal.'),
            ($input['state_registration'] ?? '') !== '' ? self::requirePattern($input['state_registration'], '/^[0-9A-Za-z.\-\/]{2,20}$/', 'Inscrição estadual inválida.') : null,
            TaxRegime::tryFrom($input['tax_regime'] ?? '') ?? throw new DomainException('Escolha o regime tributário.'),
            self::requirePattern($input['municipality_code'] ?? '', '/^\d{7}$/', 'Código IBGE do município: 7 números (ex.: 3550308 para São Paulo).'),
            self::requireText($input['address'] ?? '', 5, 255, 'Informe o endereço do hotel.'),
            self::requirePattern($input['service_code'] ?? '', '/^\d{2}\.\d{2}(\.\d{2})?$/', 'Código do serviço inválido (hospedagem: 09.01).'),
            self::parseRate($input['iss_rate'] ?? '', self::MIN_ISS_BP, self::MAX_ISS_BP, 'A alíquota de ISS deve ficar entre 2% e 5%.'),
            self::parseRate(($input['icms_rate'] ?? '') !== '' ? $input['icms_rate'] : '0', 0, self::MAX_ICMS_BP, 'Alíquota de ICMS inválida.'),
            self::requirePattern($input['series'] ?? '', '/^\d{1,5}$/', 'A série tem de 1 a 5 números.'),
            IssWithholdingRule::tryFrom($input['iss_withholding'] ?? '') ?? throw new DomainException('Escolha a regra de retenção do ISS.'),
        ));
    }

    /** "5", "5,00" or "5.00" (%) → 500 basis points. */
    public static function parseRate(string $value, int $minBasisPoints, int $maxBasisPoints, string $error): int
    {
        $percent = filter_var(str_replace(',', '.', trim($value)), FILTER_VALIDATE_FLOAT);
        $basisPoints = $percent === false ? -1 : (int) round($percent * 100);
        if ($basisPoints < $minBasisPoints || $basisPoints > $maxBasisPoints) {
            throw new DomainException($error);
        }

        return $basisPoints;
    }

    private static function requireText(string $value, int $minLength, int $maxLength, string $error): string
    {
        $value = trim($value);
        if (mb_strlen($value) < $minLength || mb_strlen($value) > $maxLength) {
            throw new DomainException($error);
        }

        return $value;
    }

    private static function requirePattern(string $value, string $pattern, string $error): string
    {
        $value = trim($value);
        if (!preg_match($pattern, $value)) {
            throw new DomainException($error);
        }

        return $value;
    }
}
