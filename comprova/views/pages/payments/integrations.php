<?php
/**
 * @var Comprova\View\View $view
 * @var string $webhookUrl
 * @var bool $hasWebhookSecret
 * @var string $emailDriver
 * @var string $whatsappDriver
 * @var string $whatsappTemplate
 */
$examplePayload = <<<'JSON'
{
  "external_id": "PAG-2026-0105",
  "amount_cents": 245000,
  "paid_at": "2026-11-03T14:32:00-03:00",
  "description": "Serviços de outubro",
  "beneficiary": {
    "name": "Mariana Lima",
    "email": "mariana.lima@email.com",
    "whatsapp_phone": "(11) 98765-4321",
    "whatsapp_opt_in": true
  },
  "receipt": { "file_name": "comprovante.pdf", "content_base64": "JVBERi0xLjQK..." }
}
JSON;
$channels = [['E-mail', $emailDriver], ['WhatsApp', $whatsappDriver]];
?>
<?= $view->partial('page-heading', [
    'eyebrow' => 'Workspace',
    'title' => 'Integrações',
    'subtitle' => 'Conecte o sistema pagador e configure os canais de envio no arquivo .env.',
    'actions' => null,
]) ?>

<section class="panel panel-body">
    <h2>Canais de envio</h2>
    <dl class="details details-spaced">
        <?php foreach ($channels as [$label, $driver]): ?>
            <div><dt><?= e($label) ?></dt><dd><?= e($driver) ?></dd></div>
        <?php endforeach; ?>
        <div><dt>Modelo do WhatsApp</dt><dd><code><?= e($whatsappTemplate) ?></code></dd></div>
    </dl>
    <p class="hint">Para o WhatsApp real, cadastre na Meta (WhatsApp Business) um modelo de categoria <strong>Utilidade</strong>, em pt_BR, com este texto:</p>
    <pre>Olá, {{1}}! {{2}} realizou um pagamento de {{3}} em {{4}}. O comprovante foi enviado para o e-mail {{5}}.</pre>
</section>

<section class="panel panel-body">
    <h2>Webhook do sistema pagador</h2>
    <p>Quando um pagamento for confirmado, o sistema pagador envia um <code>POST</code> para:</p>
    <pre><?= e($webhookUrl) ?></pre>
    <p class="<?= $hasWebhookSecret ? 'hint' : 'alert alert-error' ?>">
        <?= $hasWebhookSecret ? 'Segredo configurado (WEBHOOK_SECRET).' : 'Defina WEBHOOK_SECRET no .env: sem ele, todas as chamadas são recusadas.' ?>
    </p>
    <p>Cabeçalhos obrigatórios: <code>X-Comprova-Timestamp</code> (Unix, até 5 min de diferença) e <code>X-Comprova-Signature</code> = HMAC-SHA256 em hexadecimal de <code>{timestamp}.{corpo}</code> com o segredo. O mesmo <code>external_id</code> nunca gera dois envios.</p>
    <pre><?= e($examplePayload) ?></pre>
    <p class="hint">Para testar: <code>php bin/send-test-webhook.php http://localhost/PROTO_IA_27.10/comprova</code></p>
</section>
