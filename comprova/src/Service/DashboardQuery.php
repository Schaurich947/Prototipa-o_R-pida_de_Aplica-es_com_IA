<?php

declare(strict_types=1);

namespace Comprova\Service;

use Comprova\Domain\Clock;
use Comprova\Domain\DeliveryStatus;
use Comprova\Repository\BeneficiaryRepository;
use Comprova\Repository\NotificationRepository;
use Comprova\Repository\PaymentRepository;

final class DashboardQuery
{
    private const RECENT_LIMIT = 50;

    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly BeneficiaryRepository $beneficiaries,
        private readonly NotificationRepository $notifications,
        private readonly Clock $clock,
    ) {
    }

    /** @return array{paymentsThisMonth: int, valueThisMonthCents: int, sent: int, inQueue: int, problems: int} */
    public function getStats(): array
    {
        $month = $this->payments->summarizeSince($this->clock->now()->modify('first day of this month')->setTime(0, 0));
        $byStatus = $this->notifications->countByStatus();

        return [
            'paymentsThisMonth' => $month['count'],
            'valueThisMonthCents' => $month['totalCents'],
            'sent' => $byStatus['sent'] ?? 0,
            'inQueue' => ($byStatus['pending'] ?? 0) + ($byStatus['waiting'] ?? 0) + ($byStatus['sending'] ?? 0),
            'problems' => ($byStatus['failed'] ?? 0) + ($byStatus['skipped'] ?? 0),
        ];
    }

    /** @return list<PaymentRow> */
    public function getRecentRows(?DeliveryStatus $filter): array
    {
        $payments = $this->payments->findRecent(self::RECENT_LIMIT);
        $notificationsByPayment = $this->notifications->findByPaymentIds(array_map(static fn ($payment) => $payment->id, $payments));
        $beneficiariesById = $this->beneficiaries->findByIds(array_map(static fn ($payment) => $payment->beneficiaryId, $payments));
        $rows = [];
        foreach ($payments as $payment) {
            $beneficiary = $beneficiariesById[$payment->beneficiaryId] ?? null;
            $row = $beneficiary ? new PaymentRow($payment, $beneficiary, $notificationsByPayment[$payment->id] ?? []) : null;
            if ($row !== null && ($filter === null || $row->deliveryStatus === $filter)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function getRow(int $paymentId): ?PaymentRow
    {
        $payment = $this->payments->findById($paymentId);
        $beneficiary = $payment ? $this->beneficiaries->findById($payment->beneficiaryId) : null;
        if ($payment === null || $beneficiary === null) {
            return null;
        }

        return new PaymentRow($payment, $beneficiary, $this->notifications->findByPaymentIds([$payment->id])[$payment->id] ?? []);
    }
}
