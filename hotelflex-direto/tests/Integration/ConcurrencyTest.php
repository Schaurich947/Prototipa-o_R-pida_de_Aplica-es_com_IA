<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Integration;

use HotelFlex\Repository\ChargeRepository;
use HotelFlex\Service\StoredReceipt;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Objetivo: ações simultâneas não geram reserva dupla, cobrança dupla, lotação estourada nem mensagem repetida.
 * Cada cenário dispara vários processos PHP independentes que começam no mesmo instante.
 */
#[TestDox('Concorrência (várias pessoas ao mesmo tempo)')]
final class ConcurrencyTest extends IntegrationTestCase
{
    #[TestDox('8 hóspedes tentam reservar o mesmo quarto no mesmo instante: só 1 consegue')]
    public function testOnlyOneOfManySimultaneousBookingsWins(): void
    {
        $guests = ['ana@hotelflex.test', 'bruno@hotelflex.test', 'carla@hotelflex.test'];
        $commands = array_map(static fn (int $index) => ['book', $guests[$index % 3], '1', '2026-12-10', '2026-12-12'], range(0, 7));

        $results = $this->runInParallel($commands);

        $this->assertSame(1, count(array_filter($results, static fn ($result) => $result === 'ok')), implode(' | ', $results));
        $this->assertSame(1, $this->countRows('SELECT COUNT(*) FROM reservations WHERE room_id = 1'));
    }

    #[TestDox('2 recepcionistas aprovam o mesmo comprovante ao mesmo tempo: aprova uma vez só')]
    public function testSameReceiptIsApprovedOnlyOnce(): void
    {
        $reservation = $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12');
        $chargeId = $this->openCharges($reservation)[0]['id'];
        (new ChargeRepository($this->db))->attachReceipt($chargeId, new StoredReceipt('teste.pdf', 'pix.pdf', 'application/pdf'));

        $results = $this->runInParallel([
            ['approve', 'recepcao.centro@hotelflex.test', (string) $chargeId],
            ['approve', 'admin@hotelflex.test', (string) $chargeId],
        ]);

        $this->assertSame(1, count(array_filter($results, static fn ($result) => $result === 'ok')), implode(' | ', $results));
        $this->assertSame('confirmed', $this->reservationStatus($reservation));
        $this->assertSame(1, $this->countRows("SELECT COUNT(*) FROM notifications WHERE reservation_id = :id AND subject LIKE '%confirmada%' AND channel = 'email'", ['id' => $reservation]));
    }

    #[TestDox('4 clientes reservam café para 3 pessoas cada com 10 lugares: só 3 conseguem (9 lugares)')]
    public function testBreakfastCapacityHoldsUnderConcurrency(): void
    {
        $commands = array_map(static fn () => ['breakfast', 'carla@hotelflex.test', '2026-12-05', '3'], range(1, 4));

        $results = $this->runInParallel($commands);

        $this->assertSame(3, count(array_filter($results, static fn ($result) => $result === 'ok')), implode(' | ', $results));
        $this->assertSame(9, $this->countRows("SELECT SUM(people) FROM breakfast_bookings WHERE breakfast_date = '2026-12-05'"));
    }

    #[TestDox('3 workers processam a mesma fila de 60 mensagens: cada mensagem sai exatamente uma vez')]
    public function testParallelWorkersNeverSendTwice(): void
    {
        $insert = $this->db->prepare("INSERT INTO notifications (user_id, channel, recipient, subject, body) VALUES (4, 'email', 'ana@hotelflex.test', :subject, 'teste')");
        for ($index = 1; $index <= 60; $index++) {
            $insert->execute(['subject' => "Mensagem {$index}"]);
        }

        $this->runInParallel([['dispatch'], ['dispatch'], ['dispatch']]);

        $this->assertSame(60, $this->countRows("SELECT COUNT(*) FROM notifications WHERE status = 'sent'"));
        $this->assertCount(60, glob($this->storageRoot . '/storage/outbox/*.eml'), 'Cada mensagem gera um único arquivo enviado');
    }

    #[TestDox('3 workers emitem a mesma fila de 12 notas: cada nota sai uma vez, com números 1 a 12 sem repetir nem pular')]
    public function testParallelInvoiceWorkersKeepNumberingSequential(): void
    {
        $insert = $this->db->prepare(
            "INSERT INTO invoices (hotel_id, user_id, kind, service_type, source_type, source_id, taker_name, taker_document_type, taker_document, description, amount_cents, competence_date, next_attempt_at, created_at)
             VALUES (1, 4, 'nfse', 'hospedagem', 'reserva', :source_id, 'Ana Souza', 'cpf', '39053344705', 'Hospedagem de teste', 30400, '2026-12-01', '2026-12-01 09:00:00', '2026-12-01 09:00:00')",
        );
        for ($index = 1; $index <= 12; $index++) {
            $insert->execute(['source_id' => $index]);
        }

        $this->runInParallel([['invoices'], ['invoices'], ['invoices']]);

        $this->assertSame(12, $this->countRows("SELECT COUNT(*) FROM invoices WHERE status = 'authorized'"));
        $this->assertSame(12, $this->countRows('SELECT COUNT(DISTINCT number) FROM invoices WHERE number BETWEEN 1 AND 12'));
        $this->assertSame(13, $this->countRows('SELECT next_number FROM hotel_fiscal_profiles WHERE hotel_id = 1'));
        $this->assertSame(12, $this->countRows("SELECT COUNT(*) FROM tax_ledger WHERE tax = 'iss'"), 'ISS lançado uma vez por nota');
        $this->assertCount(12, glob($this->storageRoot . '/storage/fiscal/*.xml'));
    }

    /** @param list<list<string>> $commands @return list<string> output of each process */
    private function runInParallel(array $commands): array
    {
        $startAt = (string) (microtime(true) + 1.0);
        $script = __DIR__ . '/workers/run-action.php';
        $processes = [];
        foreach ($commands as $command) {
            $arguments = array_map('escapeshellarg', [PHP_BINARY, $script, $startAt, $this->storageRoot, ...$command]);
            $processes[] = proc_open(implode(' ', $arguments), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            $outputs[] = $pipes;
        }
        $results = [];
        foreach ($processes as $index => $process) {
            $results[] = trim(stream_get_contents($outputs[$index][1]) . stream_get_contents($outputs[$index][2]));
            proc_close($process);
        }

        return $results;
    }
}
