<?php

declare(strict_types=1);

namespace Comprova\Domain;

final readonly class User
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self((int) $row['id'], (string) $row['name'], (string) $row['email']);
    }
}
