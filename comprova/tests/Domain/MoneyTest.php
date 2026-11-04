<?php

declare(strict_types=1);

namespace Comprova\Tests\Domain;

use Comprova\Domain\DomainException;
use Comprova\Domain\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testParsesBrazilianAndDotDecimalFormatsIntoCents(): void
    {
        $this->assertSame(123456, Money::parseToCents('1.234,56'));
        $this->assertSame(123450, Money::parseToCents('1234,5'));
        $this->assertSame(123456, Money::parseToCents('1234.56'));
        $this->assertSame(1000, Money::parseToCents('R$ 10'));
    }

    public function testRejectsZeroAndGarbage(): void
    {
        foreach (['0', '0,00', 'abc', '12,345'] as $input) {
            try {
                Money::parseToCents($input);
                $this->fail("Deveria rejeitar {$input}");
            } catch (DomainException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testFormatsCentsAsReais(): void
    {
        $this->assertSame('R$ 2.450,00', Money::format(245000));
    }
}
