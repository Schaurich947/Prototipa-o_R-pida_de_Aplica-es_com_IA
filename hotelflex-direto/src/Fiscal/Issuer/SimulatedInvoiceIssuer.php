<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Issuer;

use HotelFlex\Fiscal\Domain\HotelFiscalProfile;
use HotelFlex\Fiscal\Domain\Invoice;
use RuntimeException;

/**
 * Behaves like the authority without sending anything: validates, "authorizes" with a fake access key
 * and keeps the XML in storage/fiscal. Every document is marked as a simulation, with no fiscal value.
 */
final class SimulatedInvoiceIssuer implements InvoiceIssuer
{
    public const NAME = 'simulacao';

    public function __construct(
        private readonly string $storagePath,
        private readonly InvoiceXmlBuilder $xml,
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function issue(Invoice $invoice, HotelFiscalProfile $issuer): IssueResult
    {
        $dpsId = (string) $invoice->dpsId;
        $existing = $this->findByDpsId($dpsId);
        if ($existing !== null) {
            return $existing;
        }
        $rejection = self::validate($invoice);
        if ($rejection !== null) {
            return IssueResult::rejected($rejection);
        }
        $accessKey = 'SIMULACAO' . strtoupper(substr(hash('sha256', $dpsId), 0, 41));
        $this->write($dpsId, ['status' => 'autorizada', 'access_key' => $accessKey]);
        $this->writeXml($dpsId, $this->xml->build($invoice, $issuer, $accessKey));

        return IssueResult::authorized($accessKey);
    }

    public function findByDpsId(string $dpsId): ?IssueResult
    {
        $file = $this->getPath($dpsId, 'json');
        if (!is_file($file)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($file), true);

        return is_array($data) ? IssueResult::authorized((string) $data['access_key']) : null;
    }

    public function cancel(Invoice $invoice, string $reason): IssueResult
    {
        $this->write((string) $invoice->dpsId, ['status' => 'cancelada', 'access_key' => $invoice->accessKey, 'motivo' => $reason]);

        return IssueResult::authorized((string) $invoice->accessKey);
    }

    /** Same checks the real layout makes on the taker. */
    private static function validate(Invoice $invoice): ?string
    {
        if ($invoice->amountCents <= 0) {
            return 'Valor do serviço deve ser maior que zero.';
        }
        if ($invoice->taker->isCompany() && $invoice->taker->address === null) {
            return 'Tomador pessoa jurídica sem endereço.';
        }

        return null;
    }

    /** @param array<string, mixed> $data */
    private function write(string $dpsId, array $data): void
    {
        $this->ensureFolder();
        file_put_contents($this->getPath($dpsId, 'json'), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    private function writeXml(string $dpsId, string $xml): void
    {
        file_put_contents($this->getPath($dpsId, 'xml'), $xml, LOCK_EX);
    }

    private function getPath(string $dpsId, string $extension): string
    {
        if (!preg_match('/^[A-Z0-9]{10,60}$/', $dpsId)) {
            throw new RuntimeException('Identificador de DPS inválido.');
        }

        return "{$this->storagePath}/{$dpsId}.{$extension}";
    }

    private function ensureFolder(): void
    {
        if (!is_dir($this->storagePath) && !mkdir($this->storagePath, 0770, true) && !is_dir($this->storagePath)) {
            throw new RuntimeException('Não foi possível criar a pasta de notas simuladas.');
        }
    }
}
