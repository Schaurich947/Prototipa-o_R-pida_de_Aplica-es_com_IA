<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Service;

use HotelFlex\Fiscal\Domain\BillingAddress;
use HotelFlex\Fiscal\Domain\BillingKind;
use HotelFlex\Fiscal\Domain\CompanyRecord;
use HotelFlex\Fiscal\Domain\TaxDocument;

final readonly class ParsedBillingProfile
{
    public function __construct(
        public BillingKind $kind,
        public string $name,
        public TaxDocument $document,
        public ?string $email,
        public ?BillingAddress $address,
        public string $country,
        public ?CompanyRecord $company,
        public ?string $stateRegistration,
    ) {
    }
}
