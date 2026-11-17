<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Integration;

use HotelFlex\Domain\PaymentPlan;
use HotelFlex\Fiscal\Domain\Invoice;
use HotelFlex\Fiscal\Domain\InvoiceSource;

/** Helpers for the fiscal scenarios: invoice data, a complete stay up to check-out, and the issue queue. */
abstract class FiscalTestCase extends IntegrationTestCase
{
    /** @param array<string, string> $input billing form fields */
    protected function createBillingProfile(string $email, array $input): int
    {
        return $this->app->fiscal()->billingProfileService()->create($this->user($email), $input);
    }

    /** Books one night today (01/12/2026) with the guest's own CPF unless another profile is given, pays, checks in and checks out. */
    protected function stayAndCheckOut(string $guestEmail, int $roomId = 1, ?int $billingProfileId = null, string $staffEmail = 'recepcao.centro@hotelflex.test'): int
    {
        $this->clock->travelTo(self::DEFAULT_NOW);
        $reservationId = $this->app->reservationService()->book($this->user($guestEmail), $roomId, self::dates('2026-12-01', '2026-12-02'), PaymentPlan::Full, true, $billingProfileId ?? self::BILLING_PROFILES[$guestEmail]);
        $this->payFirstCharge($reservationId, $staffEmail);
        $this->app->roomService()->checkIn($this->user($staffEmail), $reservationId);
        $this->clock->travelTo('2026-12-02 11:00:00');
        $this->app->roomService()->checkOut($this->user($staffEmail), $reservationId);

        return $reservationId;
    }

    /** @return list<Invoice> */
    protected function invoicesOf(int $reservationId): array
    {
        return $this->app->fiscal()->invoiceService()->findBySource(InvoiceSource::Reservation, $reservationId);
    }

    /** @return array{authorized: int, rejected: int, retrying: int} */
    protected function processQueue(): array
    {
        return $this->app->fiscal()->invoiceProcessor()->processDue();
    }

    /** @return array<string, int> "tax:nature" => cents */
    protected function ledgerTotals(int $hotelId = 1): array
    {
        $statement = $this->db->prepare("SELECT CONCAT(tax, ':', nature) AS line, SUM(amount_cents) AS cents FROM tax_ledger WHERE hotel_id = :hotel_id GROUP BY tax, nature ORDER BY tax, nature");
        $statement->execute(['hotel_id' => $hotelId]);

        $totals = array_map('intval', array_column($statement->fetchAll(), 'cents', 'line'));
        ksort($totals);

        return $totals;
    }
}
