<?php

declare(strict_types=1);

namespace HotelFlex\Config;

use RuntimeException;

final class Env
{
    /** @var array<string, string> */
    private array $values;

    /** @param array<string, string> $values */
    private function __construct(array $values)
    {
        $this->values = $values;
    }

    /** Real environment variables win over the .env file, so servers can override it. */
    public static function load(string $filePath): self
    {
        $fileValues = is_readable($filePath) ? self::parseFile($filePath) : [];
        $environmentValues = array_filter(getenv(), 'is_string');

        return new self(array_merge($fileValues, $environmentValues));
    }

    public function get(string $key, ?string $default = null): string
    {
        $value = $this->values[$key] ?? $default;
        if ($value === null) {
            throw new RuntimeException("Variável de ambiente obrigatória ausente: {$key}");
        }

        return $value;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->values[$key] ?? null;
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /** @return array<string, string> */
    private static function parseFile(string $filePath): array
    {
        $values = [];
        foreach (file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = trim($value, " \t\"'");
        }

        return $values;
    }
}
