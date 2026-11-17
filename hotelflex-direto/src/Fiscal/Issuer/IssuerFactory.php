<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Issuer;

use HotelFlex\Config\Env;
use RuntimeException;

/** FISCAL_DRIVER in .env. Phase 1 ships only the simulation; "nacional" and "gateway" come with a real certificate. */
final class IssuerFactory
{
    public function __construct(
        private readonly Env $env,
        private readonly string $storagePath,
    ) {
    }

    public function create(): InvoiceIssuer
    {
        $driver = $this->env->get('FISCAL_DRIVER', SimulatedInvoiceIssuer::NAME);
        if ($driver !== SimulatedInvoiceIssuer::NAME) {
            throw new RuntimeException("FISCAL_DRIVER={$driver} ainda não está disponível. Use FISCAL_DRIVER=simulacao.");
        }

        return new SimulatedInvoiceIssuer($this->storagePath, new InvoiceXmlBuilder());
    }
}
