<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Fiscal;

use HotelFlex\Domain\DomainException;
use HotelFlex\Fiscal\Domain\DocumentType;
use HotelFlex\Fiscal\Domain\TaxDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[TestDox('CPF e CNPJ (inclusive o CNPJ alfanumérico)')]
final class TaxDocumentTest extends TestCase
{
    #[TestDox('CPF válido é aceito com ou sem pontuação e formatado na nota')]
    public function testValidCpfIsNormalizedAndFormatted(): void
    {
        $cpf = TaxDocument::cpf('390.533.447-05');

        $this->assertSame(DocumentType::Cpf, $cpf->type);
        $this->assertSame('39053344705', $cpf->value);
        $this->assertSame('390.533.447-05', $cpf->format());
    }

    #[TestDox('Na tela, o CPF aparece mascarado (LGPD); o CNPJ, que é público, aparece inteiro')]
    public function testScreensMaskOnlyPersonalDocuments(): void
    {
        $this->assertSame('***.533.447-**', TaxDocument::cpf('39053344705')->mask());
        $this->assertSame('11.222.333/0001-81', TaxDocument::cnpj('11222333000181')->mask());
    }

    #[TestDox('CNPJ alfanumérico (formato novo de julho/2026) é aceito: exemplo oficial 12.ABC.345/01DE-35')]
    public function testAlphanumericCnpjIsAccepted(): void
    {
        $cnpj = TaxDocument::fromBrazilian('12.abc.345/01de-35');

        $this->assertSame(DocumentType::Cnpj, $cnpj->type);
        $this->assertSame('12ABC34501DE35', $cnpj->value);
        $this->assertSame('12.ABC.345/01DE-35', $cnpj->format());
    }

    /** @return array<string, array{string}> */
    public static function invalidDocuments(): array
    {
        return [
            'CPF com dígito errado' => ['390.533.447-06'],
            'CPF com todos os dígitos iguais' => ['111.111.111-11'],
            'CNPJ com dígito errado' => ['11.222.333/0001-82'],
            'CNPJ alfanumérico com dígito errado' => ['12.ABC.345/01DE-36'],
            'CNPJ com letra no dígito verificador' => ['12.ABC.345/01DE-3A'],
            'tamanho errado' => ['1234567'],
        ];
    }

    #[DataProvider('invalidDocuments')]
    #[TestDox('Documento inválido é recusado: $input')]
    public function testInvalidDocumentsAreRejected(string $input): void
    {
        $this->expectException(DomainException::class);

        TaxDocument::fromBrazilian($input);
    }

    #[TestDox('CNPJ no campo de CPF (e vice-versa) é recusado')]
    public function testDocumentTypeMustMatchTheField(): void
    {
        $this->expectException(DomainException::class);

        TaxDocument::cpf('11222333000181');
    }

    #[TestDox('Documento estrangeiro aceita letras e números (passaporte)')]
    public function testForeignDocumentAcceptsPassportNumbers(): void
    {
        $this->assertSame('AB1234567', TaxDocument::foreign('ab-123.4567')->value);
        $this->assertSame('******567', TaxDocument::foreign('AB1234567')->mask());
    }
}
