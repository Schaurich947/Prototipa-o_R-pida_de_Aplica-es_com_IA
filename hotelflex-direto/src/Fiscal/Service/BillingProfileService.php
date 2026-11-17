<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Service;

use HotelFlex\Domain\Clock;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\User;
use HotelFlex\Fiscal\Domain\BillingProfile;
use HotelFlex\Fiscal\Domain\CompanyRecord;
use HotelFlex\Fiscal\Domain\InvoiceTaker;
use HotelFlex\Fiscal\Domain\TaxDocument;
use HotelFlex\Fiscal\Lookup\CompanyLookup;
use HotelFlex\Fiscal\Repository\BillingProfileRepository;
use HotelFlex\Fiscal\Repository\HotelFiscalProfileRepository;

/** The customer's invoice data, chosen before paying. The taker may be someone else or a company. */
final class BillingProfileService
{
    public const ANONYMOUS_CHOICE = 'consumidor';
    private const MAX_PROFILES = 10;

    public function __construct(
        private readonly BillingProfileRepository $profiles,
        private readonly HotelFiscalProfileRepository $fiscalProfiles,
        private readonly CompanyLookup $companies,
        private readonly BillingProfileInputParser $parser,
        private readonly Clock $clock,
    ) {
    }

    /** @return list<BillingProfile> */
    public function listFor(User $owner): array
    {
        return $this->profiles->findActiveByUser($owner->id);
    }

    /** @param array<string, string> $input form fields */
    public function create(User $owner, array $input): int
    {
        if (count($this->profiles->findActiveByUser($owner->id)) >= self::MAX_PROFILES) {
            throw new DomainException('Limite de ' . self::MAX_PROFILES . ' cadastros. Remova um que não usa mais.');
        }
        $parsed = $this->parser->parse($owner, $input, fn (TaxDocument $cnpj) => $this->findActiveCompany($cnpj));

        return $this->profiles->create($owner->id, $parsed->kind, $parsed->name, $parsed->document, $parsed->email, $parsed->address, $parsed->country, $parsed->company, $this->clock->now());
    }

    public function archive(User $owner, int $profileId): void
    {
        if (!$this->profiles->archive($profileId, $owner->id, $this->clock->now())) {
            throw new DomainException('Registro não encontrado.');
        }
    }

    /** Lookup for the "Consultar CNPJ" button: fills the form and warns about irregular companies. */
    public function lookUpCompany(string $cnpjInput): ?CompanyRecord
    {
        return $this->companies->find(TaxDocument::cnpj($cnpjInput));
    }

    /**
     * Validates the choice made on the payment screen. Server side: the radio button alone proves nothing.
     *
     * @return int|null the billing profile, or null for "consumer not identified"
     */
    public function resolveForPayment(User $owner, int $hotelId, string $choice): ?int
    {
        if ($choice === '') {
            throw new DomainException('Escolha em nome de quem sai a nota fiscal.');
        }
        if ($choice === self::ANONYMOUS_CHOICE) {
            $this->assertAnonymousAllowed($hotelId);

            return null;
        }
        $profile = ctype_digit($choice) ? $this->profiles->findById((int) $choice) : null;
        $isOwnActive = $profile !== null && $profile->userId === $owner->id && in_array($profile->id, array_map(static fn ($item) => $item->id, $this->listFor($owner)), true);
        if (!$isOwnActive) {
            throw new DomainException('Dados para a nota fiscal não encontrados. Escolha outro cadastro.');
        }

        return $profile->id;
    }

    /** Text shown before the invoice exists: "the invoice will be issued to ...". */
    public function describeChoice(?int $billingProfileId): string
    {
        $profile = $billingProfileId !== null ? $this->profiles->findById($billingProfileId) : null;

        return $profile?->describe() ?? InvoiceTaker::ANONYMOUS_NAME;
    }

    public function allowsAnonymous(int $hotelId): bool
    {
        return $this->fiscalProfiles->findByHotel($hotelId)?->allowsAnonymousConsumer ?? true;
    }

    private function assertAnonymousAllowed(int $hotelId): void
    {
        if (!$this->allowsAnonymous($hotelId)) {
            throw new DomainException('Este hotel exige CPF ou CNPJ na nota fiscal.');
        }
    }

    /** Irregular companies cannot be takers; a lookup outage does not block the customer. */
    private function findActiveCompany(TaxDocument $cnpj): ?CompanyRecord
    {
        $company = $this->companies->find($cnpj);
        if ($company !== null && !$company->isActive()) {
            throw new DomainException("CNPJ com situação {$company->status} na Receita Federal: não é possível emitir nota para esta empresa.");
        }

        return $company;
    }
}
