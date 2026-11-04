<?php

declare(strict_types=1);

namespace Comprova\Http;

use DateTimeImmutable;
use Comprova\Domain\DomainException;

final readonly class HttpRequest
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, mixed> $files
     * @param array<string, mixed> $server
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $query,
        public array $body,
        public array $files = [],
        public array $server = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $browserPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $path = Url::stripBasePath(rawurldecode($browserPath));

        return new self(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), rtrim($path, '/') ?: '/', $_GET, $_POST, $_FILES, $_SERVER);
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

    public function getHeader(string $name): string
    {
        return trim((string) ($this->server['HTTP_' . strtoupper(str_replace('-', '_', $name))] ?? ''));
    }

    public function readRawBody(int $maxBytes): string
    {
        return (string) file_get_contents('php://input', false, null, 0, $maxBytes + 1);
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
