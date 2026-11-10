<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Domain;

use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\PhoneNumber;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase
{
    public function testAcceptsCommonBrazilianFormats(): void
    {
        foreach (['(11) 98765-4321', '11987654321', '+55 11 98765-4321'] as $input) {
            $this->assertSame('5511987654321', PhoneNumber::fromBrazilianInput($input)->e164Digits);
        }
    }

    public function testRejectsLandlineBecauseWhatsAppNeedsAMobile(): void
    {
        $this->expectException(DomainException::class);

        PhoneNumber::fromBrazilianInput('(11) 3456-7890');
    }

    public function testFormatsAndMasksForScreens(): void
    {
        $phone = PhoneNumber::fromBrazilianInput('11987654321');

        $this->assertSame('(11) 98765-4321', $phone->format());
        $this->assertSame('(11) *****-4321', $phone->masked());
    }
}
