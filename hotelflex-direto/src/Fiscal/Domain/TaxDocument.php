<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

use HotelFlex\Domain\DomainException;

/**
 * CPF, CNPJ (numeric or the alphanumeric format issued since July 2026) or a foreign document.
 * Always stored normalized: upper case, without dots, slashes or dashes.
 */
final readonly class TaxDocument
{
    private const CPF_LENGTH = 11;
    private const CNPJ_LENGTH = 14;

    private function __construct(
        public DocumentType $type,
        public string $value,
    ) {
    }

    /** Accepts CPF or CNPJ typed with or without punctuation; validates the check digits. */
    public static function fromBrazilian(string $input): self
    {
        $value = self::normalize($input);
        if (strlen($value) === self::CPF_LENGTH && TaxDocumentValidator::isValidCpf($value)) {
            return new self(DocumentType::Cpf, $value);
        }
        if (strlen($value) === self::CNPJ_LENGTH && TaxDocumentValidator::isValidCnpj($value)) {
            return new self(DocumentType::Cnpj, $value);
        }
        throw new DomainException('CPF ou CNPJ inválido. Confira os números digitados.');
    }

    public static function cpf(string $input): self
    {
        $document = self::fromBrazilian($input);
        if ($document->type !== DocumentType::Cpf) {
            throw new DomainException('Informe um CPF válido.');
        }

        return $document;
    }

    public static function cnpj(string $input): self
    {
        $document = self::fromBrazilian($input);
        if ($document->type !== DocumentType::Cnpj) {
            throw new DomainException('Informe um CNPJ válido.');
        }

        return $document;
    }

    public static function foreign(string $input): self
    {
        $value = self::normalize($input);
        if (!preg_match('/^[A-Z0-9]{5,20}$/', $value)) {
            throw new DomainException('Documento estrangeiro inválido: use de 5 a 20 letras ou números.');
        }

        return new self(DocumentType::Foreign, $value);
    }

    public static function fromStored(DocumentType $type, string $value): self
    {
        return new self($type, $value);
    }

    public function format(): string
    {
        return match ($this->type) {
            DocumentType::Cpf => vsprintf('%s.%s.%s-%s', [substr($this->value, 0, 3), substr($this->value, 3, 3), substr($this->value, 6, 3), substr($this->value, 9, 2)]),
            DocumentType::Cnpj => vsprintf('%s.%s.%s/%s-%s', [substr($this->value, 0, 2), substr($this->value, 2, 3), substr($this->value, 5, 3), substr($this->value, 8, 4), substr($this->value, 12, 2)]),
            DocumentType::Foreign => $this->value,
        };
    }

    /** LGPD: screens show only part of a person's document. Company data is public. */
    public function mask(): string
    {
        if ($this->type === DocumentType::Cnpj) {
            return $this->format();
        }
        if ($this->type === DocumentType::Cpf) {
            return '***.' . substr($this->value, 3, 3) . '.' . substr($this->value, 6, 3) . '-**';
        }

        return str_repeat('*', max(0, strlen($this->value) - 3)) . substr($this->value, -3);
    }

    private static function normalize(string $input): string
    {
        return strtoupper((string) preg_replace('/[\s.\/-]/', '', $input));
    }
}
