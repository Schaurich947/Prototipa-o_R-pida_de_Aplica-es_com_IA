<?php

declare(strict_types=1);

namespace Comprova\Tests\Http;

use Comprova\Http\WebhookSignature;
use PHPUnit\Framework\TestCase;

final class WebhookSignatureTest extends TestCase
{
    private const SECRET = 'segredo-de-teste';

    public function testAcceptsCorrectSignatureWithinTheTimeWindow(): void
    {
        $signature = WebhookSignature::sign(self::SECRET, 1000, '{"a":1}');

        $this->assertTrue(WebhookSignature::isValid(self::SECRET, '1000', $signature, '{"a":1}', 1100));
    }

    public function testRejectsTamperedBody(): void
    {
        $signature = WebhookSignature::sign(self::SECRET, 1000, '{"amount_cents":100}');

        $this->assertFalse(WebhookSignature::isValid(self::SECRET, '1000', $signature, '{"amount_cents":999999}', 1000));
    }

    public function testRejectsReplayedOldRequestAndMissingSecret(): void
    {
        $signature = WebhookSignature::sign(self::SECRET, 1000, '{}');

        $this->assertFalse(WebhookSignature::isValid(self::SECRET, '1000', $signature, '{}', 1000 + 301));
        $this->assertFalse(WebhookSignature::isValid('', '1000', $signature, '{}', 1000));
    }
}
