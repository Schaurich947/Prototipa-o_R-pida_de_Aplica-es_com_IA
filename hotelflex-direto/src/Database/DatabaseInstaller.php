<?php

declare(strict_types=1);

namespace HotelFlex\Database;

use HotelFlex\Config\Env;
use InvalidArgumentException;
use PDO;

/**
 * Creates the database, the least-privilege app user and the tables.
 * Runs only from the command line with the admin account.
 */
final class DatabaseInstaller
{
    private const SAFE_IDENTIFIER = '/^[A-Za-z0-9_]{1,64}$/';

    public function __construct(
        private readonly PDO $adminConnection,
        private readonly Env $env,
        private readonly string $databasePath,
    ) {
    }

    /** @return list<string> progress messages */
    public function install(bool $withSeed): array
    {
        $databaseName = $this->requireIdentifier('DB_NAME');
        $this->adminConnection->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $messages = ["Banco {$databaseName} pronto."];
        $messages[] = $this->createAppUser($databaseName);

        $this->adminConnection->exec("USE `{$databaseName}`");
        $this->runSqlFile('schema.sql');
        $messages[] = 'Tabelas criadas.';
        if ($withSeed) {
            $this->runSqlFile('seed.sql');
            $messages[] = 'Dados de demonstração inseridos.';
        }

        return $messages;
    }

    /** Identifiers cannot be bound as parameters, so they are whitelisted; the password is quoted by PDO. */
    private function createAppUser(string $databaseName): string
    {
        $appUser = $this->requireIdentifier('DB_USER');
        if ($appUser === $this->env->get('DB_ADMIN_USER')) {
            return "Aviso: DB_USER igual ao admin ({$appUser}); use um usuário próprio fora do ambiente local.";
        }
        $password = $this->adminConnection->quote($this->env->get('DB_PASSWORD', ''));
        $this->adminConnection->exec("CREATE USER IF NOT EXISTS '{$appUser}'@'localhost' IDENTIFIED BY {$password}");
        $this->adminConnection->exec("ALTER USER '{$appUser}'@'localhost' IDENTIFIED BY {$password}");
        $this->adminConnection->exec("GRANT SELECT, INSERT, UPDATE ON `{$databaseName}`.* TO '{$appUser}'@'localhost'");

        return "Usuário {$appUser} pronto (somente SELECT, INSERT e UPDATE).";
    }

    private function requireIdentifier(string $key): string
    {
        $value = $this->env->get($key);
        if (!preg_match(self::SAFE_IDENTIFIER, $value)) {
            throw new InvalidArgumentException("{$key} deve conter apenas letras, números e _.");
        }

        return $value;
    }

    private function runSqlFile(string $fileName): void
    {
        $this->adminConnection->exec((string) file_get_contents("{$this->databasePath}/{$fileName}"));
    }
}
