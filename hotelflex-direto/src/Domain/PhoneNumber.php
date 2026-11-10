<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

/** Brazilian mobile number stored in E.164 digits (e.g. 5511987654321), the format WhatsApp expects. */
final readonly class PhoneNumber
{
    private const COUNTRY_CODE = '55';

    private function __construct(public string $e164Digits)
    {
    }

    /** Accepts "(11) 98765-4321", "11987654321" or "+55 11 98765-4321". */
    public static function fromBrazilianInput(string $input): self
    {
        $digits = preg_replace('/\D+/', '', $input) ?? '';
        if (strlen($digits) === 13 && str_starts_with($digits, self::COUNTRY_CODE)) {
            $digits = substr($digits, 2);
        }
        $isMobile = strlen($digits) === 11 && $digits[2] === '9' && $digits[0] !== '0';
        if (!$isMobile) {
            throw new DomainException('Informe um celular brasileiro com DDD, por exemplo (11) 98765-4321.');
        }

        return new self(self::COUNTRY_CODE . $digits);
    }

    public static function fromE164Digits(string $digits): self
    {
        return self::fromBrazilianInput($digits);
    }

    public function format(): string
    {
        $national = substr($this->e164Digits, 2);

        return sprintf('(%s) %s-%s', substr($national, 0, 2), substr($national, 2, 5), substr($national, 7));
    }

    /** Shows only the last digits, for screens and logs. */
    public function masked(): string
    {
        return '(' . substr($this->e164Digits, 2, 2) . ') *****-' . substr($this->e164Digits, -4);
    }
}
