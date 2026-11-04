<?php

declare(strict_types=1);

namespace Comprova\Service;

use Comprova\Domain\Beneficiary;
use Comprova\Domain\Money;
use Comprova\Domain\Payment;
use Comprova\Notification\EmailMessage;
use Comprova\Notification\WhatsAppMessage;

/** Texts sent to the beneficiary. The WhatsApp text mirrors the approved template "pagamento_efetuado". */
final class MessageComposer
{
    public function __construct(
        private readonly string $companyName,
        private readonly string $whatsappTemplate,
        private readonly string $whatsappLanguage,
    ) {
    }

    public function composeEmail(Payment $payment, Beneficiary $beneficiary, string $receiptPath): EmailMessage
    {
        $amount = Money::format($payment->amountCents);
        $lines = [
            "Olá, {$beneficiary->name}.",
            '',
            "{$this->companyName} realizou um pagamento para você.",
            '',
            "Valor: {$amount}",
            'Data: ' . $payment->paidAt->format('d/m/Y H:i'),
        ];
        if ($payment->description !== null) {
            $lines[] = "Referente a: {$payment->description}";
        }
        array_push($lines, '', 'O comprovante está anexado a este e-mail.', '', 'Mensagem automática enviada pelo Comprova. Não responda.');

        return new EmailMessage(
            $beneficiary->email,
            $beneficiary->name,
            "Comprovante de pagamento · {$this->companyName} · {$amount}",
            implode("\n", $lines),
            $receiptPath,
            $payment->receiptOriginalName,
            $payment->receiptMime,
        );
    }

    public function composeWhatsApp(Payment $payment, Beneficiary $beneficiary, string $toDigits): WhatsAppMessage
    {
        $parameters = [
            $beneficiary->name,
            $this->companyName,
            Money::format($payment->amountCents),
            $payment->paidAt->format('d/m/Y'),
            self::maskEmail($beneficiary->email),
        ];
        $preview = sprintf('Olá, %s! %s realizou um pagamento de %s em %s. O comprovante foi enviado para o e-mail %s.', ...$parameters);

        return new WhatsAppMessage($toDigits, $this->whatsappTemplate, $this->whatsappLanguage, $parameters, $preview);
    }

    /** The WhatsApp text points to the inbox without exposing the full address. */
    public static function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2) + [1 => ''];

        return mb_substr($local, 0, 2) . str_repeat('*', max(3, mb_strlen($local) - 2)) . '@' . $domain;
    }
}
