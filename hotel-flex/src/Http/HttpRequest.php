<?php

declare(strict_types=1);

namespace HotelFlex\Http;

use DateTimeImmutable;
use HotelFlex\Domain\DomainException;

final readonly class HttpRequest
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $query,
        public array $body,
    ) {
    }

    public static function fromGlobals(): self
    {
        $browserPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $path = Url::stripBasePath(rawurldecode($browserPath));

        return new self(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), rtrim($path, '/') ?: '/', $_GET, $_POST);
    }

    public function getQueryInt(string $key): ?int
    {
        return self::toPositiveInt($this->query[$key] ?? null);
    }

    public function getBodyString(string $key): string
    {
        $value = $this->body[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    /** Server-side validation: rejects anything that is not an integer inside the range. */
    public function requireBodyInt(string $key, int $min, int $max): int
    {
        $value = filter_var($this->body[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
        if ($value === false) {
            throw new DomainException('Valor inválido enviado no formulário.');
        }

        return $value;
    }

    public function getQueryString(string $key): string
    {
        $value = $this->query[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    /** Accepts only real calendar dates in the HTML date input format (YYYY-MM-DD). */
    public static function parseDate(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
    }

    public function requireBodyDate(string $key): DateTimeImmutable
    {
        return self::parseDate($this->getBodyString($key)) ?? throw new DomainException('Informe uma data válida.');
    }

    private static function toPositiveInt(mixed $value): ?int
    {
        $result = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $result === false ? null : $result;
    }
}
