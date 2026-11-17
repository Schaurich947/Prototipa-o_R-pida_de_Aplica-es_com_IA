<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Service;

use HotelFlex\Database\TransactionRunner;
use HotelFlex\Domain\Clock;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\Role;
use HotelFlex\Domain\User;
use HotelFlex\Fiscal\Domain\Invoice;
use HotelFlex\Fiscal\Domain\InvoiceSource;
use HotelFlex\Fiscal\Issuer\InvoiceIssuer;
use HotelFlex\Fiscal\Repository\InvoiceRepository;
use HotelFlex\Fiscal\Repository\TaxLedgerRepository;

/** What people do with invoices: view, cancel and send again. */
final class InvoiceService
{
    /** The national NFS-e requires a cancellation reason of at least 15 characters. */
    public const MIN_CANCEL_REASON_LENGTH = 15;

    public function __construct(
        private readonly InvoiceRepository $invoices,
        private readonly TaxLedgerRepository $ledger,
        private readonly InvoiceIssuer $issuer,
        private readonly TransactionRunner $transaction,
        private readonly Clock $clock,
    ) {
    }

    /** The customer sees their own invoices; the reception sees its hotel's; the admin sees all. */
    public function getVisibleInvoice(User $actor, int $invoiceId): Invoice
    {
        $invoice = $this->invoices->findById($invoiceId);
        $canSee = $invoice !== null && ($actor->canManageHotel($invoice->hotelId) || ($actor->role === Role::Guest && $invoice->userId === $actor->id));
        if (!$canSee) {
            throw new DomainException('Registro não encontrado.');
        }

        return $invoice;
    }

    /** @return list<Invoice> */
    public function findBySource(InvoiceSource $source, int $sourceId): array
    {
        return $this->invoices->findBySource($source, $sourceId);
    }

    /** @return list<Invoice> */
    public function findByHotelAndMonth(int $hotelId, string $month): array
    {
        return $this->invoices->findByHotelAndMonth($hotelId, $month);
    }

    public function cancel(User $actor, int $invoiceId, string $reason): void
    {
        $invoice = $this->getManagedInvoice($actor, $invoiceId);
        $reason = mb_substr(trim($reason), 0, 255);
        if (mb_strlen($reason) < self::MIN_CANCEL_REASON_LENGTH) {
            throw new DomainException('Explique o motivo do cancelamento (mínimo de ' . self::MIN_CANCEL_REASON_LENGTH . ' caracteres).');
        }
        $result = $this->issuer->cancel($invoice, $reason);
        if (!$result->isAuthorized) {
            throw new DomainException('O cancelamento não foi aceito: ' . ($result->message ?? 'tente novamente.'));
        }
        $this->transaction->run(function () use ($invoice, $reason): void {
            if (!$this->invoices->markCancelled($invoice->id, $reason, $this->clock->now())) {
                throw new DomainException('Só notas autorizadas podem ser canceladas.');
            }
            $this->ledger->record($invoice, -1, $this->clock->now());
        });
    }

    /** After the hotel fixes its fiscal data, a rejected invoice goes back to the queue. */
    public function requeue(User $actor, int $invoiceId): void
    {
        $invoice = $this->getManagedInvoice($actor, $invoiceId);
        if (!$this->invoices->requeue($invoice->id, $this->clock->now())) {
            throw new DomainException('Só notas rejeitadas podem ser reenviadas.');
        }
    }

    private function getManagedInvoice(User $actor, int $invoiceId): Invoice
    {
        $invoice = $this->invoices->findById($invoiceId);
        if ($invoice === null || !$actor->canManageHotel($invoice->hotelId)) {
            throw new DomainException('Registro não encontrado.');
        }

        return $invoice;
    }
}
