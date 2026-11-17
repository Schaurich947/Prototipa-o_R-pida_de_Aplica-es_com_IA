<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Lookup;

use HotelFlex\Fiscal\Domain\CompanyRecord;
use HotelFlex\Fiscal\Domain\TaxDocument;

interface CompanyLookup
{
    /**
     * @return CompanyRecord|null null when the service could not answer (the profile is saved as "not checked")
     * @throws \HotelFlex\Domain\DomainException when the CNPJ does not exist at the Receita Federal
     */
    public function find(TaxDocument $cnpj): ?CompanyRecord;
}
