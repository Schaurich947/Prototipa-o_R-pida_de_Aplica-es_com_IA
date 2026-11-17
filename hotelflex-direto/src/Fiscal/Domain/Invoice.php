<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

use DateTimeImmutable;

final readonly class Invoice
{
    public function __construct(
        public int $id,
        public int $hotelId,
        public int $userId,
        public InvoiceKind $kind,
        public ServiceType $serviceType,
        public InvoiceSource $source,
        public int $sourceId,
        public InvoiceStatus $status,
        public ?string $series,
        public ?int $number,
        /** Unique id of the declaration sent to the issuer; retries reuse it, so the same sale is never issued twice. */
        public ?string $dpsId,
        public ?string $accessKey,
        public ?string $issuerName,
        public InvoiceTaker $taker,
        public string $description,
        public int $amountCents,
        public TaxBreakdown $taxes,
        public DateTimeImmutable $competenceDate,
        public ?DateTimeImmutable $issuedAt,
        public int $attempts,
        public ?string $lastError,
        public ?string $cancelReason,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        $optional = static fn (string $key): ?string => $row[$key] !== null ? (string) $row[$key] : null;

        return new self(
            (int) $row['id'], (int) $row['hotel_id'], (int) $row['user_id'],
            InvoiceKind::from((string) $row['kind']), ServiceType::from((string) $row['service_type']),
            InvoiceSource::from((string) $row['source_type']), (int) $row['source_id'],
            InvoiceStatus::from((string) $row['status']),
            $optional('series'), $row['number'] !== null ? (int) $row['number'] : null,
            $optional('dps_id'), $optional('access_key'), $optional('issuer'),
            self::readTaker($row), (string) $row['description'], (int) $row['amount_cents'], self::readTaxes($row),
            new DateTimeImmutable((string) $row['competence_date']),
            $row['issued_at'] !== null ? new DateTimeImmutable((string) $row['issued_at']) : null,
            (int) $row['attempts'], $optional('last_error'), $optional('cancel_reason'),
        );
    }

    public function formatNumber(): string
    {
        return $this->number !== null ? sprintf('%s-%06d', $this->series, $this->number) : 'sem número';
    }

    /** @param array<string, mixed> $row */
    private static function readTaker(array $row): InvoiceTaker
    {
        $document = $row['taker_document'] !== null
            ? TaxDocument::fromStored(DocumentType::from((string) $row['taker_document_type']), (string) $row['taker_document'])
            : null;

        return new InvoiceTaker(
            (string) $row['taker_name'], $document,
            $row['taker_email'] !== null ? (string) $row['taker_email'] : null,
            $row['taker_address'] !== null ? (string) $row['taker_address'] : null,
            $row['taker_municipality_code'] !== null ? (string) $row['taker_municipality_code'] : null,
            $row['taker_is_simples'] !== null ? (bool) $row['taker_is_simples'] : null,
        );
    }

    /** @param array<string, mixed> $row */
    private static function readTaxes(array $row): TaxBreakdown
    {
        return new TaxBreakdown((int) $row['iss_cents'], (bool) $row['iss_withheld'], (int) $row['icms_cents'], (int) $row['cbs_cents'], (int) $row['ibs_cents'], (bool) $row['reform_informative'], (bool) $row['acquirer_credit']);
    }
}
