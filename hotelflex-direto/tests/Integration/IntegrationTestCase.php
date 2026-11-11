<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Integration;

use DateTimeImmutable;
use HotelFlex\Bootstrap\Container;
use HotelFlex\Config\Env;
use HotelFlex\Database\ConnectionFactory;
use HotelFlex\Database\DatabaseInstaller;
use HotelFlex\Domain\PaymentPlan;
use HotelFlex\Domain\StayDates;
use HotelFlex\Domain\User;
use HotelFlex\Http\Session;
use HotelFlex\Repository\ChargeRepository;
use HotelFlex\Repository\UserRepository;
use HotelFlex\Service\StoredReceipt;
use HotelFlex\Tests\Support\TestClock;
use HotelFlex\View\View;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Runs the real services against a dedicated database ("<DB_NAME>_test"), rebuilt before every test,
 * with a frozen clock (default: Tuesday 01/12/2026 10:00) and files written to a temporary folder.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected const DEFAULT_NOW = '2026-12-01 10:00:00';

    protected Container $app;
    protected TestClock $clock;
    protected PDO $db;
    protected string $storageRoot;

    public static function createTestEnv(): Env
    {
        $projectRoot = dirname(__DIR__, 2);
        $fileEnv = Env::load($projectRoot . '/.env');
        $testDatabase = preg_replace('/(_test)?$/', '_test', $fileEnv->get('DB_NAME'), 1);
        putenv("DB_NAME={$testDatabase}");
        putenv('MAIL_DRIVER=log');
        putenv('WHATSAPP_DRIVER=log');
        $env = Env::load($projectRoot . '/.env');
        if (!str_ends_with($env->get('DB_NAME'), '_test')) {
            throw new RuntimeException('Por segurança, os testes só rodam em um banco terminado em _test.');
        }

        return $env;
    }

    public static function rebuildDatabase(Env $env): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $admin = ConnectionFactory::createAdminConnection($env);
        (new DatabaseInstaller($admin, $env, $projectRoot . '/database'))->install(false);
        $admin->exec((string) file_get_contents(__DIR__ . '/fixtures.sql'));
    }

    protected function setUp(): void
    {
        date_default_timezone_set('America/Sao_Paulo');
        $env = self::createTestEnv();
        self::rebuildDatabase($env);
        $this->storageRoot = sys_get_temp_dir() . '/hotelflex-test-' . bin2hex(random_bytes(4));
        mkdir($this->storageRoot . '/storage/receipts', 0777, true);
        mkdir($this->storageRoot . '/storage/outbox', 0777, true);
        $this->clock = new TestClock(static::DEFAULT_NOW);
        $this->db = ConnectionFactory::createAppConnection($env);
        $this->app = new Container($this->db, $env, new Session(), new View(dirname(__DIR__, 2) . '/views'), $this->storageRoot, $this->clock);
    }

    protected function user(string $email): User
    {
        $credentials = (new UserRepository($this->db))->findCredentialsByEmail($email);

        return $credentials['user'] ?? throw new RuntimeException("Usuário de teste não encontrado: {$email}");
    }

    protected static function dates(string $checkIn, string $checkOut): StayDates
    {
        return new StayDates(new DateTimeImmutable($checkIn), new DateTimeImmutable($checkOut));
    }

    protected function book(string $guestEmail, int $roomId, string $checkIn, string $checkOut, PaymentPlan $plan = PaymentPlan::Full): int
    {
        return $this->app->reservationService()->book($this->user($guestEmail), $roomId, self::dates($checkIn, $checkOut), $plan, true);
    }

    /** The guest's receipt arrives (as the upload would store it) and the hotel approves it. */
    protected function payFirstCharge(int $reservationId, string $staffEmail = 'recepcao.centro@hotelflex.test'): void
    {
        $charge = $this->openCharges($reservationId)[0];
        (new ChargeRepository($this->db))->attachReceipt($charge['id'], new StoredReceipt('teste.pdf', 'comprovante.pdf', 'application/pdf'));
        $this->app->chargeService()->reviewReceipt($this->user($staffEmail), $charge['id'], true, '');
    }

    /** @return list<array{id: int, kind: string, amount: int, status: string, description: string}> */
    protected function charges(int $reservationId): array
    {
        $statement = $this->db->prepare('SELECT id, kind, amount_cents AS amount, status, description FROM charges WHERE reservation_id = :id ORDER BY id');
        $statement->execute(['id' => $reservationId]);

        return array_map(static fn (array $row) => ['id' => (int) $row['id'], 'kind' => $row['kind'], 'amount' => (int) $row['amount'], 'status' => $row['status'], 'description' => $row['description']], $statement->fetchAll());
    }

    /** @return list<array{id: int, kind: string, amount: int, status: string, description: string}> */
    protected function openCharges(int $reservationId): array
    {
        return array_values(array_filter($this->charges($reservationId), static fn (array $charge) => $charge['status'] === 'pending'));
    }

    protected function reservationStatus(int $reservationId): string
    {
        $statement = $this->db->prepare('SELECT status FROM reservations WHERE id = :id');
        $statement->execute(['id' => $reservationId]);

        return (string) $statement->fetchColumn();
    }

    /** @param array<string, mixed> $params */
    protected function countRows(string $sql, array $params = []): int
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }
}
