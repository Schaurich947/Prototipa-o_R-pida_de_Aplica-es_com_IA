<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Pdf;

use HotelFlex\Domain\Hotel;
use HotelFlex\Fiscal\Domain\HotelFiscalProfile;
use HotelFlex\Fiscal\Domain\Invoice;

/** Everything the DANFSe / DANFE NFC-e templates print. */
final readonly class InvoicePdfData
{
    /** @param list<InvoiceLine> $lines items (NFC-e); empty for a service invoice */
    public function __construct(
        public Invoice $invoice,
        public HotelFiscalProfile $issuer,
        public Hotel $hotel,
        public array $lines,
        public int $discountCents,
        public string $paymentLabel,
        /** Page of this invoice in the app: the simulation's "public query", encoded in the QR code. */
        public string $consultUrl,
        public string $qrCodeDataUri,
    ) {
    }

    public function grossCents(): int
    {
        return $this->invoice->amountCents + $this->discountCents;
    }

    /** What the issuer receives after taxes withheld by the taker. */
    public function netCents(): int
    {
        return $this->invoice->amountCents - ($this->invoice->taxes->isIssWithheld ? $this->invoice->taxes->issCents : 0);
    }

    /** Groups of 4 characters, as printed on the official documents. */
    public function formatAccessKey(): string
    {
        return trim(chunk_split((string) $this->invoice->accessKey, 4, ' '));
    }
}
