<?php

declare(strict_types=1);

namespace Comprova\Bootstrap;

use Comprova\Config\Env;
use Comprova\Database\TransactionRunner;
use Comprova\Domain\Clock;
use Comprova\Domain\SystemClock;
use Comprova\Http\Auth;
use Comprova\Http\Controller\ActionRunner;
use Comprova\Http\Controller\AuthController;
use Comprova\Http\Controller\IntegrationsController;
use Comprova\Http\Controller\LiveUpdateController;
use Comprova\Http\Controller\PaymentsController;
use Comprova\Http\Controller\WebhookController;
use Comprova\Http\Session;
use Comprova\Notification\SenderFactory;
use Comprova\Repository\BeneficiaryRepository;
use Comprova\Repository\ChangeTrackerRepository;
use Comprova\Repository\NotificationRepository;
use Comprova\Repository\PaymentRepository;
use Comprova\Repository\UserRepository;
use Comprova\Service\AuthService;
use Comprova\Service\DashboardQuery;
use Comprova\Service\MessageComposer;
use Comprova\Service\NotificationDispatcher;
use Comprova\Service\PaymentRegistrationService;
use Comprova\Service\ReceiptStorage;
use Comprova\Service\RetryService;
use Comprova\View\View;
use PDO;

/** Composition root: the only place that wires concrete implementations together. */
final class Container
{
    private readonly Clock $clock;
    private readonly UserRepository $users;
    private readonly BeneficiaryRepository $beneficiaries;
    private readonly PaymentRepository $payments;
    private readonly NotificationRepository $notifications;
    private readonly ReceiptStorage $receipts;
    private readonly TransactionRunner $transaction;
    private readonly SenderFactory $senders;
    private readonly Auth $auth;

    public function __construct(
        private readonly PDO $connection,
        private readonly Env $env,
        private readonly Session $session,
        private readonly View $view,
        string $projectRoot,
    ) {
        $this->clock = new SystemClock();
        $this->users = new UserRepository($connection);
        $this->beneficiaries = new BeneficiaryRepository($connection);
        $this->payments = new PaymentRepository($connection);
        $this->notifications = new NotificationRepository($connection);
        $this->receipts = new ReceiptStorage($projectRoot . '/storage/receipts');
        $this->transaction = new TransactionRunner($connection);
        $this->senders = new SenderFactory($env, $projectRoot . '/storage/outbox');
        $this->auth = new Auth($session, $this->users);
    }

    public function auth(): Auth
    {
        return $this->auth;
    }

    public function authController(): AuthController
    {
        return new AuthController($this->session, $this->auth, new AuthService($this->users), new ActionRunner($this->session), $this->view);
    }

    public function paymentsController(): PaymentsController
    {
        return new PaymentsController(
            $this->auth, $this->session, $this->dashboardQuery(), $this->registrationService(), $this->receipts,
            new RetryService($this->notifications), $this->dispatcher(), new ActionRunner($this->session), $this->view,
            $this->env->get('APP_ENV', 'production') !== 'production',
        );
    }

    public function integrationsController(): IntegrationsController
    {
        return new IntegrationsController(
            $this->view, $this->senders->createEmailSender(), $this->senders->createWhatsAppSender(),
            $this->env->get('WEBHOOK_SECRET', '') !== '', $this->senders->getWhatsAppTemplate(),
        );
    }

    public function webhookController(): WebhookController
    {
        return new WebhookController($this->registrationService(), $this->receipts, $this->clock, $this->env->get('WEBHOOK_SECRET', ''));
    }

    public function liveUpdateController(): LiveUpdateController
    {
        return new LiveUpdateController(new ChangeTrackerRepository($this->connection));
    }

    /** Also used by the CLI worker. */
    public function dispatcher(): NotificationDispatcher
    {
        $composer = new MessageComposer($this->env->get('COMPANY_NAME', 'Minha empresa'), $this->senders->getWhatsAppTemplate(), $this->senders->getWhatsAppLanguage());

        return new NotificationDispatcher(
            $this->notifications, $this->payments, $this->beneficiaries, $this->receipts, $composer,
            $this->senders->createEmailSender(), $this->senders->createWhatsAppSender(), $this->transaction, $this->clock,
        );
    }

    private function registrationService(): PaymentRegistrationService
    {
        return new PaymentRegistrationService($this->beneficiaries, $this->payments, $this->notifications, $this->receipts, $this->transaction, $this->clock);
    }

    private function dashboardQuery(): DashboardQuery
    {
        return new DashboardQuery($this->payments, $this->beneficiaries, $this->notifications, $this->clock);
    }
}
