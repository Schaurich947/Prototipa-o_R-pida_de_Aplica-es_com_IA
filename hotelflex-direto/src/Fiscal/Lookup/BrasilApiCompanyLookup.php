<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Lookup;

use HotelFlex\Domain\Clock;
use HotelFlex\Domain\DomainException;
use HotelFlex\Fiscal\Domain\BillingAddress;
use HotelFlex\Fiscal\Domain\CompanyRecord;
use HotelFlex\Fiscal\Domain\TaxDocument;

/**
 * Receita Federal open data through BrasilAPI (free, no key). Only the CNPJ, which is public company data, leaves the server.
 * The alphanumeric CNPJ is not served yet, so it is saved as "not checked".
 */
final class BrasilApiCompanyLookup implements CompanyLookup
{
    public const SOURCE = 'receita-brasilapi';
    private const ENDPOINT = 'https://brasilapi.com.br/api/cnpj/v1/';
    private const TIMEOUT_SECONDS = 5;

    public function __construct(private readonly Clock $clock)
    {
    }

    public function find(TaxDocument $cnpj): ?CompanyRecord
    {
        if (!ctype_digit($cnpj->value)) {
            return null;
        }
        [$status, $body] = $this->request($cnpj->value);
        if ($status === 404) {
            throw new DomainException('CNPJ não encontrado na Receita Federal. Confira o número.');
        }
        $data = $status === 200 ? json_decode($body, true) : null;
        if (!is_array($data)) {
            error_log("[hotelflex] consulta de CNPJ indisponível (HTTP {$status})");

            return null;
        }

        return $this->toRecord($cnpj, $data);
    }

    /** @return array{0: int, 1: string} HTTP status and body (0 when the request failed) */
    private function request(string $cnpj): array
    {
        $context = stream_context_create(['http' => ['timeout' => self::TIMEOUT_SECONDS, 'ignore_errors' => true, 'header' => "Accept: application/json\r\nUser-Agent: hotelflex-direto\r\n"]]);
        $body = @file_get_contents(self::ENDPOINT . $cnpj, false, $context);
        $statusLine = $http_response_header[0] ?? '';

        return [preg_match('/\s(\d{3})\s?/', $statusLine, $match) ? (int) $match[1] : 0, is_string($body) ? $body : ''];
    }

    /** @param array<string, mixed> $data */
    private function toRecord(TaxDocument $cnpj, array $data): CompanyRecord
    {
        $street = trim(($data['descricao_tipo_de_logradouro'] ?? '') . ' ' . ($data['logradouro'] ?? ''));
        $address = new BillingAddress($street, (string) ($data['numero'] ?? 'S/N'), (string) ($data['bairro'] ?? ''), (string) ($data['municipio'] ?? ''), (string) ($data['uf'] ?? ''), str_pad((string) ($data['cep'] ?? ''), 8, '0', STR_PAD_LEFT), isset($data['codigo_municipio_ibge']) ? (string) $data['codigo_municipio_ibge'] : null);

        return new CompanyRecord(
            $cnpj->value,
            (string) ($data['razao_social'] ?? ''),
            strtoupper((string) ($data['descricao_situacao_cadastral'] ?? '')),
            isset($data['opcao_pelo_simples']) ? (bool) $data['opcao_pelo_simples'] : null,
            isset($data['opcao_pelo_mei']) ? (bool) $data['opcao_pelo_mei'] : null,
            $address,
            self::SOURCE,
            $this->clock->now(),
        );
    }
}
