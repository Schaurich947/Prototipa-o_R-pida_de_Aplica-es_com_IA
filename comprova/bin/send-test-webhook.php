<?php

declare(strict_types=1);

// Simulates the payer system: sends a signed payment to the webhook.
// Usage: php bin/send-test-webhook.php <app-url> [--email=voce@email.com] [--celular=11987654321]
//        [--nome="Seu Nome"] [--valor=150,00] [--id=PAG-123]
// Repeating the same --id shows the duplicate protection (no second payment).

use Comprova\Config\Env;
use Comprova\Database\SampleReceipt;
use Comprova\Domain\Money;
use Comprova\Http\WebhookSignature;

$projectRoot = dirname(__DIR__);
$composerAutoload = $projectRoot . '/vendor/autoload.php';
require is_file($composerAutoload) ? $composerAutoload : $projectRoot . '/src/autoload.php';

if (PHP_SAPI !== 'cli' || !isset($argv[1]) || str_starts_with($argv[1], '--')) {
    fwrite(STDERR, "Uso: php bin/send-test-webhook.php <url-do-app> [--email=...] [--celular=...] [--nome=...] [--valor=150,00] [--id=...]\n");
    exit(1);
}

$options = getopt('', ['email:', 'celular:', 'nome:', 'valor:', 'id:']);
$option = static fn (string $key, string $default): string => is_string($options[$key] ?? null) ? $options[$key] : $default;
$env = Env::load($projectRoot . '/.env');
$payload = [
    'external_id' => $option('id', 'TESTE-' . date('YmdHis')),
    'amount_cents' => Money::parseToCents($option('valor', '1.570,90')),
    'paid_at' => (new DateTimeImmutable())->format(DATE_ATOM),
    'description' => 'Pagamento de teste via webhook',
    'beneficiary' => [
        'name' => $option('nome', 'Joana Teste'),
        'email' => $option('email', 'joana.teste@email.com'),
        'whatsapp_phone' => $option('celular', '(11) 91234-5678'),
        'whatsapp_opt_in' => true,
    ],
    'receipt' => ['file_name' => 'comprovante-teste.pdf', 'content_base64' => base64_encode(SampleReceipt::build('Comprovante de teste'))],
];
$body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
$timestamp = time();

$curl = curl_init(rtrim($argv[1], '/') . '/api/webhooks/pagamentos');
curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Comprova-Timestamp: ' . $timestamp,
        'X-Comprova-Signature: ' . WebhookSignature::sign($env->get('WEBHOOK_SECRET', ''), $timestamp, $body),
    ],
]);
$response = curl_exec($curl);
printf("HTTP %d: %s\n", curl_getinfo($curl, CURLINFO_RESPONSE_CODE), $response === false ? curl_error($curl) : $response);
