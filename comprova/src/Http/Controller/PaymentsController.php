<?php

declare(strict_types=1);

namespace Comprova\Http\Controller;

use Comprova\Domain\DeliveryStatus;
use Comprova\Domain\DomainException;
use Comprova\Http\Auth;
use Comprova\Http\HttpRequest;
use Comprova\Http\Router;
use Comprova\Http\Session;
use Comprova\Service\DashboardQuery;
use Comprova\Service\NotificationDispatcher;
use Comprova\Service\PaymentRegistrationService;
use Comprova\Service\ReceiptStorage;
use Comprova\Service\RetryService;
use Comprova\View\View;

final class PaymentsController
{
    public function __construct(
        private readonly Auth $auth,
        private readonly Session $session,
        private readonly DashboardQuery $dashboard,
        private readonly PaymentRegistrationService $registration,
        private readonly ReceiptStorage $receipts,
        private readonly RetryService $retries,
        private readonly NotificationDispatcher $dispatcher,
        private readonly ActionRunner $actions,
        private readonly View $view,
        private readonly bool $canProcessQueueFromScreen,
    ) {
    }

    public function showDashboard(HttpRequest $request): void
    {
        $filter = DeliveryStatus::tryFrom($request->getQueryString('status'));
        $this->view->render('payments/dashboard', [
            'pageTitle' => 'Comprovantes',
            'stats' => $this->dashboard->getStats(),
            'rows' => $this->dashboard->getRecentRows($filter),
            'activeFilter' => $filter,
            'canProcessQueue' => $this->canProcessQueueFromScreen,
        ]);
    }

    public function showForm(): void
    {
        $this->view->render('payments/new', ['pageTitle' => 'Registrar pagamento', 'old' => $this->session->pullOldInput(), 'liveRefresh' => false]);
    }

    public function store(HttpRequest $request): never
    {
        $operator = $this->auth->requireUser();
        $this->actions->run(function () use ($request, $operator): string {
            $input = PaymentFormParser::parse($request, $operator->id);
            $receiptFile = is_array($request->files['receipt'] ?? null) ? $request->files['receipt'] : [];
            $result = $this->registration->register($input, fn () => $this->receipts->storeUpload($receiptFile));

            return '/comprovantes/' . $result['paymentId'];
        }, 'Pagamento registrado. O comprovante entrou na fila de envio.', '/comprovantes/novo', $request->body);
    }

    /** @param array<string, int> $params */
    public function showDetail(HttpRequest $request, array $params): void
    {
        $row = $this->dashboard->getRow($params['id']) ?? throw new DomainException('Pagamento não encontrado.');
        $this->view->render('payments/detail', ['pageTitle' => 'Pagamento #' . $row->payment->id, 'row' => $row, 'canProcessQueue' => $this->canProcessQueueFromScreen]);
    }

    /** @param array<string, int> $params */
    public function retry(HttpRequest $request, array $params): never
    {
        $this->actions->run(
            fn () => '/comprovantes/' . $this->retries->retry($params['id']),
            'Envio colocado na fila novamente.',
            Router::HOME_PATH,
        );
    }

    /** @param array<string, int> $params */
    public function downloadReceipt(HttpRequest $request, array $params): never
    {
        $row = $this->dashboard->getRow($params['id']) ?? throw new DomainException('Pagamento não encontrado.');
        ReceiptDownload::send($this->receipts->getAbsolutePath($row->payment->receiptPath), $row->payment->receiptOriginalName, $row->payment->receiptMime);
    }

    /** Development shortcut; in production the CLI worker (bin/send-notifications.php) processes the queue. */
    public function processQueue(HttpRequest $request): never
    {
        $paymentId = filter_var($request->body['payment_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $returnTo = $paymentId ? "/comprovantes/{$paymentId}" : Router::HOME_PATH;
        $this->actions->run(function (): void {
            if (!$this->canProcessQueueFromScreen) {
                throw new DomainException('Em produção, os envios são processados pelo agendador.');
            }
            $summary = $this->dispatcher->processDue();
            $this->session->flash('success', "Fila processada: {$summary['sent']} enviado(s), {$summary['retrying']} para nova tentativa, {$summary['failed']} com falha.");
        }, '', $returnTo);
    }
}
