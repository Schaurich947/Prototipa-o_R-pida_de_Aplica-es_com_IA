# Comprova — envio automático de comprovantes

Quando um pagamento é confirmado, o Comprova envia o **comprovante por e-mail** ao recebedor. Depois que o e-mail é entregue, manda um **aviso por WhatsApp** dizendo que o pagamento foi feito e que o comprovante está no e-mail.

- **Páginas:** HTML + PHP (templates em `views/`), com o visual do protótipo original.
- **Backend:** PHP 8.2+ e MySQL/MariaDB via PDO.
- **JavaScript:** só para atualizar a tela quando o status dos envios muda.

## Fluxo de negócio

1. **Pagamento confirmado:** chega pelo webhook do sistema pagador (automático) ou pelo formulário "Registrar pagamento".
2. **E-mail:** sai com o comprovante anexado.
3. **WhatsApp:** só sai depois que o e-mail é entregue. Se o e-mail falhar de vez, o WhatsApp não é enviado, porque o texto diria que o comprovante está no e-mail. Ao reenviar o e-mail, o WhatsApp volta a aguardar.
4. **Autorização:** o WhatsApp só vai para quem autorizou receber (opt-in), e a data da autorização fica registrada (LGPD e política do WhatsApp Business).
5. **Falhas:** cada envio tem até 5 tentativas (espera de 1, 2, 4 e 8 minutos). Erros definitivos, como número sem WhatsApp, param na hora. O que falhar aparece em "Precisa de atenção", com o motivo e o botão de reenviar.
6. **Sem duplicidade:** o mesmo `external_id` do sistema pagador nunca gera dois pagamentos nem dois envios.

## Como rodar (Laragon / WAMP / XAMPP)

1. Coloque a pasta em `www`, por exemplo `C:\laragon\www\PROTO_IA_27.10\comprova`.
2. Crie o `.env`:

   ```bash
   copy .env.example .env
   ```

   Preencha `DB_PASSWORD` (o script cria o usuário com essa senha) e `WEBHOOK_SECRET` (um texto longo e aleatório). No Laragon, `DB_ADMIN_USER=root` fica com a senha vazia.
3. Crie o banco. Este comando **recria as tabelas e apaga os dados**:

   ```bash
   php bin/setup-database.php
   ```

4. Abra `http://localhost/PROTO_IA_27.10/comprova/` e entre com `admin@comprova.test` / `Demo@1234`.
5. Para processar a fila de envios, use o botão "Processar envios agora" (fora de produção) ou rode o worker:

   ```bash
   php bin/send-notifications.php --loop
   ```

6. Para simular o sistema pagador:

   ```bash
   php bin/send-test-webhook.php http://localhost/PROTO_IA_27.10/comprova
   ```

O Composer só é necessário para rodar os testes (`composer install` e depois `composer test`).

### Modo teste × envio real

| Canal | `log` (padrão) | Real |
|---|---|---|
| E-mail (`MAIL_DRIVER`) | Salva `.eml` em `storage/outbox` | `mail`: usa o `mail()` do PHP (no Laragon: Menu › Preferences › Mail Sender) |
| WhatsApp (`WHATSAPP_DRIVER`) | Salva o JSON da API em `storage/outbox` | `cloud_api`: WhatsApp Business Cloud API da Meta |

Para o WhatsApp real você precisa de uma conta no WhatsApp Business Platform (Meta), do `WHATSAPP_PHONE_NUMBER_ID`, de um token permanente (`WHATSAPP_ACCESS_TOKEN`) e de um modelo aprovado chamado `pagamento_efetuado`. O texto do modelo está na tela **Integrações**.

## Segurança

- **Comprovantes:** ficam fora de `public/`, com nome aleatório. O tipo é conferido pelo conteúdo do arquivo (PDF, PNG ou JPG, até 5 MB), e o download só funciona com login.
- **Webhook:** assinado com HMAC-SHA256 e janela de 5 minutos contra reenvio de requisições antigas.
- **Banco:** prepared statements, usuário do app só com `SELECT`, `INSERT` e `UPDATE`, e transações.
- **Fila:** usa `FOR UPDATE SKIP LOCKED`, então dois workers nunca enviam a mesma mensagem.
- **Telas:** CSRF em todos os formulários, saída escapada, CSP sem scripts inline e cabeçalhos de e-mail protegidos contra injeção.
- **Erros:** os detalhes do provedor vão só para o log do servidor.

## Estrutura

```
bin/               setup do banco, worker da fila, simulador de webhook
database/          schema.sql e seed.sql
public/            index.php, CSS e JS
src/Domain/        regras puras (valor, telefone, tentativas, status)
src/Notification/  canais de envio (e-mail, WhatsApp) e modo teste
src/Service/       cadastro de pagamento, fila de envios, consultas
src/Http/          rotas, sessão, controllers, webhook
storage/           comprovantes e saída do modo teste (fora do git)
views/             páginas HTML/PHP
```
