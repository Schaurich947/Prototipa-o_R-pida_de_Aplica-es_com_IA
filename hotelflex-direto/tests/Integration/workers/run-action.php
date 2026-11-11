<?php

declare(strict_types=1);

// Concurrency worker: waits for a shared start instant, runs one action against the test database
// and prints "ok" or "recusado: <motivo>". Launched in parallel by ConcurrencyTest.
// Usage: php run-action.php <start-unix-microtime> <storage-root> <action> [args...]

use HotelFlex\Bootstrap\Container;
use HotelFlex\Database\ConnectionFactory;
use HotelFlex\Domain\BreakfastPaymentOption;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\PaymentPlan;
use HotelFlex\Domain\StayDates;
use HotelFlex\Http\Session;
use HotelFlex\Repository\UserRepository;
use HotelFlex\Tests\Integration\IntegrationTestCase;
use HotelFlex\Tests\Support\TestClock;
use HotelFlex\View\View;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

[, $startAt, $storageRoot, $action] = $argv;
$args = array_slice($argv, 4);
date_default_timezone_set('America/Sao_Paulo');
$env = IntegrationTestCase::createTestEnv();
$db = ConnectionFactory::createAppConnection($env);
$app = new Container($db, $env, new Session(), new View(dirname(__DIR__, 3) . '/views'), $storageRoot, new TestClock('2026-12-01 10:00:00'));
$user = static fn (string $email) => (new UserRepository($db))->findCredentialsByEmail($email)['user'];

while (microtime(true) < (float) $startAt) {
    usleep(500);
}

try {
    match ($action) {
        'book' => $app->reservationService()->book($user($args[0]), (int) $args[1], new StayDates(new DateTimeImmutable($args[2]), new DateTimeImmutable($args[3])), PaymentPlan::Full, true),
        'approve' => $app->chargeService()->reviewReceipt($user($args[0]), (int) $args[1], true, ''),
        'breakfast' => $app->breakfastService()->book($user($args[0]), 1, new DateTimeImmutable($args[1]), (int) $args[2], BreakfastPaymentOption::Desk),
        'dispatch' => $app->dispatcher()->processDue(200),
    };
    echo 'ok';
} catch (DomainException $error) {
    echo 'recusado: ' . $error->getMessage();
}
