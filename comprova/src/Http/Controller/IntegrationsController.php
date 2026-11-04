<?php

declare(strict_types=1);

namespace Comprova\Http\Controller;

use Comprova\Http\Url;
use Comprova\Notification\EmailSender;
use Comprova\Notification\WhatsAppSender;
use Comprova\View\View;

final class IntegrationsController
{
    public function __construct(
        private readonly View $view,
        private readonly EmailSender $emailSender,
        private readonly WhatsAppSender $whatsAppSender,
        private readonly bool $hasWebhookSecret,
        private readonly string $whatsappTemplate,
    ) {
    }

    public function show(): void
    {
        $scheme = ($_SERVER['HTTPS'] ?? 'off') !== 'off' ? 'https' : 'http';
        $host = preg_replace('/[^A-Za-z0-9.:-]/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));

        $this->view->render('payments/integrations', [
            'pageTitle' => 'Integrações',
            'webhookUrl' => "{$scheme}://{$host}" . Url::to('/api/webhooks/pagamentos'),
            'hasWebhookSecret' => $this->hasWebhookSecret,
            'emailDriver' => $this->emailSender->describe(),
            'whatsappDriver' => $this->whatsAppSender->describe(),
            'whatsappTemplate' => $this->whatsappTemplate,
        ]);
    }
}
