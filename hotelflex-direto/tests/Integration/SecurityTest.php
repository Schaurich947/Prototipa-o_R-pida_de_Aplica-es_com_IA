<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Integration;

use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\RequestType;
use HotelFlex\Http\Session;
use HotelFlex\Repository\UserRepository;
use HotelFlex\Service\AuthService;
use HotelFlex\Service\ReceiptStorage;
use PHPUnit\Framework\Attributes\TestDox;

/** Objetivo: ninguém consegue fazer o que não pode, mesmo chamando direto, sem passar pela tela. */
#[TestDox('Segurança e permissões')]
final class SecurityTest extends IntegrationTestCase
{
    private const NOT_FOUND = 'Registro não encontrado.';

    #[TestDox('Hóspede não cancela, adia nem faz pedidos na reserva de outro hóspede')]
    public function testGuestCannotTouchAnotherGuestsReservation(): void
    {
        $anaReservation = $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12');
        $bruno = $this->user('bruno@hotelflex.test');
        $attempts = [
            fn () => $this->app->reservationService()->cancel($bruno, $anaReservation),
            fn () => $this->app->reservationService()->changeDates($bruno, $anaReservation, self::dates('2026-12-20', '2026-12-21')),
            fn () => $this->app->requestService()->createRequest($bruno, $anaReservation, RequestType::LateCheckout, 2),
        ];

        foreach ($attempts as $attempt) {
            $this->assertDeniedWith(self::NOT_FOUND, $attempt);
        }
        $this->assertSame('pending_payment', $this->reservationStatus($anaReservation));
    }

    #[TestDox('Hóspede não aprova o próprio comprovante')]
    public function testGuestCannotApproveOwnReceipt(): void
    {
        $reservation = $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12');
        $chargeId = $this->openCharges($reservation)[0]['id'];

        $this->assertDeniedWith(self::NOT_FOUND, fn () => $this->app->chargeService()->reviewReceipt($this->user('ana@hotelflex.test'), $chargeId, true, ''));
    }

    #[TestDox('Recepção de um hotel não aprova nem recebe pagamentos de outro hotel')]
    public function testStaffIsLimitedToItsOwnHotel(): void
    {
        $reservation = $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12');
        $chargeId = $this->openCharges($reservation)[0]['id'];
        $otherHotelStaff = $this->user('recepcao.marazul@hotelflex.test');

        $this->assertDeniedWith(self::NOT_FOUND, fn () => $this->app->chargeService()->reviewReceipt($otherHotelStaff, $chargeId, true, ''));
        $this->assertDeniedWith(self::NOT_FOUND, fn () => $this->app->roomService()->checkIn($otherHotelStaff, $reservation));
    }

    #[TestDox('Hóspede não baixa o comprovante de outra pessoa')]
    public function testGuestCannotDownloadSomeoneElsesReceipt(): void
    {
        $reservation = $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12');
        $chargeId = $this->openCharges($reservation)[0]['id'];

        $this->assertDeniedWith(self::NOT_FOUND, fn () => $this->app->chargeService()->getChargeForViewer($this->user('bruno@hotelflex.test'), $chargeId));
    }

    #[TestDox('Hóspede não executa ações da recepção (check-in, horários)')]
    public function testGuestCannotRunReceptionActions(): void
    {
        $reservation = $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12');
        $ana = $this->user('ana@hotelflex.test');

        $this->assertDeniedWith(self::NOT_FOUND, fn () => $this->app->roomService()->checkIn($ana, $reservation));
        $this->assertDeniedWith(self::NOT_FOUND, fn () => $this->app->roomService()->setFlexibleHours($ana, $reservation, RequestType::LateCheckout, 6));
    }

    #[TestDox('Pedido de horário antes do pagamento é recusado mesmo chamando direto')]
    public function testTimeRequestBeforePaymentIsRefused(): void
    {
        $reservation = $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12');

        $this->assertDeniedWith(
            'Os pedidos de horário ficam disponíveis depois que o pagamento da reserva for confirmado.',
            fn () => $this->app->requestService()->createRequest($this->user('ana@hotelflex.test'), $reservation, RequestType::LateCheckout, 2),
        );
    }

    #[TestDox('Arquivo que não veio de um upload real do navegador é recusado')]
    public function testOnlyRealUploadsAreAccepted(): void
    {
        $reservation = $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12');
        $fakeFile = tempnam(sys_get_temp_dir(), 'pix');
        file_put_contents($fakeFile, '%PDF-1.4 falso');

        $this->assertDeniedWith(
            'Anexe o comprovante (PDF, JPG ou PNG de até 5 MB).',
            fn () => $this->app->chargeService()->submitReceipt($this->user('ana@hotelflex.test'), $this->openCharges($reservation)[0]['id'], ['error' => UPLOAD_ERR_OK, 'tmp_name' => $fakeFile, 'name' => 'pix.pdf']),
        );
    }

    #[TestDox('Script disfarçado de PDF e arquivo acima de 5 MB são recusados pelo conteúdo')]
    public function testMaliciousOrHugeFilesAreRefused(): void
    {
        $storage = new ReceiptStorage($this->storageRoot . '/storage/receipts');

        $this->assertDeniedWith('Formato não aceito. Envie PDF, JPG ou PNG.', fn () => $storage->storeBase64(base64_encode('<?php system($_GET["c"]); ?>'), 'comprovante.pdf'));
        $this->assertDeniedWith('O comprovante deve ter até 5 MB.', fn () => $storage->storeBase64(base64_encode(str_repeat('A', ReceiptStorage::MAX_BYTES + 1)), 'grande.pdf'));
        $this->assertSame([], glob($this->storageRoot . '/storage/receipts/*'), 'Nada pode ter sido gravado');
    }

    #[TestDox('Formulário sem o token CSRF certo é rejeitado')]
    public function testCsrfTokenIsRequired(): void
    {
        $_SESSION = [];
        $session = new Session();
        $token = $session->getCsrfToken();

        $this->assertFalse($session->isValidCsrfToken(''));
        $this->assertFalse($session->isValidCsrfToken(str_repeat('0', 64)));
        $this->assertTrue($session->isValidCsrfToken($token));
    }

    #[TestDox('Login: senha errada é recusada e 5 erros seguidos bloqueiam novas tentativas')]
    public function testLoginIsThrottled(): void
    {
        $_SESSION = [];
        $session = new Session();
        $auth = new AuthService(new UserRepository($this->db), $this->clock);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->assertDeniedWith('E-mail ou senha inválidos.', fn () => $auth->authenticate('ana@hotelflex.test', 'errada'));
            $session->registerFailedLogin();
        }
        $this->assertTrue($session->isLoginLocked());
        $this->assertSame('Ana Souza', $auth->authenticate('ana@hotelflex.test', 'Demo@1234')->name, 'O bloqueio vale para a sessão do navegador; a conta e a senha continuam válidas');
    }

    private function assertDeniedWith(string $expectedMessage, callable $attempt): void
    {
        try {
            $attempt();
            $this->fail("Deveria ter sido recusado com: {$expectedMessage}");
        } catch (DomainException $error) {
            $this->assertSame($expectedMessage, $error->getMessage());
        }
    }
}
