<?php

declare(strict_types=1);

namespace Comprova\Tests\Service;

use Comprova\Domain\Beneficiary;
use Comprova\Domain\Payment;
use Comprova\Domain\PaymentSource;
use Comprova\Domain\PhoneNumber;
use Comprova\Service\MessageComposer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class MessageComposerTest extends TestCase
{
    public function testWhatsAppTellsThePaymentAndPointsToTheMaskedInbox(): void
    {
        $message = $this->composer()->composeWhatsApp($this->payment(), $this->beneficiary(), '5511987654321');

        $this->assertSame(['Mariana Lima', 'Acme Comércio', 'R$ 2.450,00', '03/11/2026', 'ma**********@email.com'], $message->templateParameters);
        $this->assertStringContainsString('O comprovante foi enviado para o e-mail', $message->previewText);
    }

    public function testEmailAttachesTheReceipt(): void
    {
        $message = $this->composer()->composeEmail($this->payment(), $this->beneficiary(), '/tmp/recibo.pdf');

        $this->assertSame('/tmp/recibo.pdf', $message->attachmentPath);
        $this->assertStringContainsString('R$ 2.450,00', $message->subject);
        $this->assertStringContainsString('Referente a: Design', $message->textBody);
    }

    private function composer(): MessageComposer
    {
        return new MessageComposer('Acme Comércio', 'pagamento_efetuado', 'pt_BR');
    }

    private function payment(): Payment
    {
        return new Payment(1, 1, 'PAG-1', PaymentSource::Webhook, 245000, 'Design', new DateTimeImmutable('2026-11-03 14:32'), 'x.pdf', 'recibo.pdf', 'application/pdf');
    }

    private function beneficiary(): Beneficiary
    {
        return new Beneficiary(1, 'Mariana Lima', 'mariana.lima@email.com', PhoneNumber::fromBrazilianInput('11987654321'), new DateTimeImmutable('2026-10-01'));
    }
}
