<?php

declare(strict_types=1);

// Teste de volume: gera muitos dados no banco de TESTE e mede o tempo das operações principais.
// Uso: composer test:volume   (ou: php tests/Performance/volume.php [quartos-por-hotel] [hoteis])
// Não altera o banco do sistema: usa "<DB_NAME>_test", recriado do zero.

use HotelFlex\Bootstrap\Container;
use HotelFlex\Database\ConnectionFactory;
use HotelFlex\Domain\StayDates;
use HotelFlex\Http\Session;
use HotelFlex\Repository\HotelRepository;
use HotelFlex\Repository\UserRepository;
use HotelFlex\Tests\Integration\IntegrationTestCase;
use HotelFlex\Tests\Performance\VolumeDataGenerator;
use HotelFlex\Tests\Support\TestClock;
use HotelFlex\View\View;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

const RUNS = 3;
$roomsPerHotel = (int) ($argv[1] ?? 25);
$hotelCount = (int) ($argv[2] ?? 20);
date_default_timezone_set('America/Sao_Paulo');

$env = IntegrationTestCase::createTestEnv();
IntegrationTestCase::rebuildDatabase($env);
echo "Gerando dados no banco {$env->get('DB_NAME')}...\n";
$admin = ConnectionFactory::createAdminConnection($env);
$admin->exec('USE `' . $env->get('DB_NAME') . '`');
$counts = (new VolumeDataGenerator($admin))->generate($hotelCount, $roomsPerHotel);

$storageRoot = sys_get_temp_dir() . '/hotelflex-volume-' . bin2hex(random_bytes(4));
mkdir($storageRoot . '/storage/outbox', 0777, true);
$db = ConnectionFactory::createAppConnection($env);
$clock = new TestClock('2026-12-01 10:00:00');
$app = new Container($db, $env, new Session(), new View(dirname(__DIR__, 2) . '/views'), $storageRoot, $clock);
$heavyGuest = (new UserRepository($db))->findCredentialsByEmail('ana@hotelflex.test')['user'];
$busiestHotel = (new HotelRepository($db))->findById(1);

/** Median of several runs in milliseconds; the first run warms caches. */
function measure(callable $operation): float
{
    $times = [];
    for ($run = 0; $run < RUNS; $run++) {
        $start = hrtime(true);
        $operation();
        $times[] = (hrtime(true) - $start) / 1e6;
    }
    sort($times);

    return $times[intdiv(RUNS, 2)];
}

$dates = new StayDates(new DateTimeImmutable('2026-12-20'), new DateTimeImmutable('2026-12-22'));
$results = [
    ['Busca de quartos (todas as cidades, 2 diárias)', "{$counts['rooms']} quartos", measure(fn () => $app->bookingSearch()->search($dates, ''))],
    ['Painel do dia de um hotel', "{$roomsPerHotel} quartos", measure(fn () => $app->panelQuery()->getRoomCards($busiestHotel, $clock->now()->setTime(0, 0)))],
    ['Minhas reservas de um hóspede frequente', "{$counts['heavyGuestReservations']} reservas", measure(fn () => $app->guestQuery()->getGroupedStays($heavyGuest))],
    ['Pagamentos a conferir de um hotel', 'fila do hotel', measure(fn () => $app->panelQuery()->getChargesToReview($busiestHotel))],
];
$queueStart = hrtime(true);
$sent = 0;
while (($batch = $app->dispatcher()->processDue(500)['sent']) > 0) {
    $sent += $batch;
}
$results[] = ['Worker enviando a fila de mensagens', "{$sent} mensagens", (hrtime(true) - $queueStart) / 1e6];

printf("\nDados: %d hotéis · %d quartos · %d hóspedes · %d reservas · %d cobranças · %d mensagens\n\n", $counts['hotels'], $counts['rooms'], $counts['users'], $counts['reservations'], $counts['charges'], $counts['notifications']);
printf("%-48s %-18s %12s\n", 'Cenário', 'Volume', 'Tempo');
echo str_repeat('-', 80) . "\n";
foreach ($results as [$scenario, $volume, $milliseconds]) {
    printf("%-48s %-18s %9.0f ms\n", $scenario, $volume, $milliseconds);
}
echo "\nTempos por operação (mediana de " . RUNS . " execuções; a fila é medida uma vez).\n";
