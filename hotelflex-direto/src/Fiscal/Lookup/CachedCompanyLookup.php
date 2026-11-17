<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Lookup;

use HotelFlex\Domain\Clock;
use HotelFlex\Fiscal\Domain\CompanyRecord;
use HotelFlex\Fiscal\Domain\TaxDocument;
use HotelFlex\Fiscal\Repository\CompanyLookupCache;

/** Keeps lookups for 30 days: the Receita Federal publishes its open data monthly. */
final class CachedCompanyLookup implements CompanyLookup
{
    private const CACHE_DAYS = 30;

    public function __construct(
        private readonly CompanyLookup $inner,
        private readonly CompanyLookupCache $cache,
        private readonly Clock $clock,
    ) {
    }

    public function find(TaxDocument $cnpj): ?CompanyRecord
    {
        $cached = $this->cache->find($cnpj->value, $this->clock->now()->modify('-' . self::CACHE_DAYS . ' days'));
        if ($cached !== null) {
            return CompanyRecord::fromArray($cached);
        }
        $record = $this->inner->find($cnpj);
        if ($record !== null) {
            $this->cache->save($cnpj->value, $record->toArray(), $this->clock->now());
        }

        return $record;
    }
}
