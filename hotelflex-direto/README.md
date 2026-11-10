# HotelFlex Direto — reserva direta com o hotel, Pix e WhatsApp

Plataforma de reservas para vários hotéis. O hóspede reserva, **paga via Pix direto para o hotel** (sem intermediário e sem comissão) e envia o comprovante; a recepção confere e confirma. Cada etapa gera **mensagens automáticas por e-mail e WhatsApp**, e há botões de **conversa direta pelo WhatsApp** entre hóspede e hotel. Os horários de entrada e saída podem ser flexibilizados e são cobrados por hora.

O sistema une dois projetos anteriores:

- **HotelFlex:** reservas, disponibilidade por quarto, entrada antecipada e saída tardia, perfis.
- **Comprova:** comprovante de pagamento, fila de mensagens com novas tentativas, e-mail e WhatsApp.

Tecnologia: HTML + PHP 8.2+ e MySQL/MariaDB (PDO), com Tailwind CSS compilado no visual do shadcn/ui. O JavaScript serve só para os sliders de horário e a atualização automática das telas.

## Ciclo da reserva

```
Aguardando pagamento → Comprovante em análise → Confirmada → Hospedado → Concluída
        │ prazo vencido            │ recusado (com motivo)     │
        ▼                          ▼                           ▼
     Expirada            volta a aguardar pagamento        Cancelada (com ou sem devolução)
```

| Etapa | O que acontece | Mensagem automática |
|---|---|---|
| Reserva criada | Gera a cobrança Pix com o prazo do hotel (ex.: 24h) | Hóspede: valor, chave Pix e prazo. Hotel: nova reserva |
| Comprovante enviado | Arquivo validado e guardado fora da pasta pública | Hotel: comprovante para conferir |
| Hotel aprova | Reserva confirmada | Hóspede: reserva confirmada |
| Hotel recusa | Hóspede vê o motivo e envia outro comprovante | Hóspede: motivo da recusa |
| Prazo vence sem comprovante | Reserva expira e o quarto é liberado | Hóspede: reserva expirada |
| Hora extra aprovada / datas alteradas | Cobrança ajustada: nova cobrança, valor atualizado ou crédito | Hóspede: nova cobrança ou crédito |
| Quarto fica pronto antes da hora | Recepção libera o quarto | Hóspede: "seu quarto já está pronto!" |
| Cancelamento | Grátis até o limite do hotel (ex.: 48h antes); depois, sem devolução | Hóspede e hotel |
| Check-out | Quarto vai para a limpeza | Hóspede: agradecimento |

O WhatsApp automático só é enviado para quem autorizou, e a data da autorização fica registrada (LGPD). A autorização é dada no cadastro e pode ser alterada em **Meu perfil**.

## Telas

| Perfil | Telas |
|---|---|
| Hóspede | Minhas reservas (todos os hotéis, com histórico), Nova reserva (entrada e saída no calendário, filtros por cidade, categoria e diária máxima, ordenação por preço ou nome), Revisão da reserva (Pix à vista; sinal de 50% via Pix + restante via Pix; ou sinal via Pix + restante no hotel em dinheiro, cartão ou Pix; aceite da política de cancelamento), Detalhe da reserva (andamento, Pix, comprovante, horários, adiar, cancelar, mensagens, WhatsApp do hotel), Meu perfil |
| Recepção | Painel do dia (chegadas, hospedados, limpeza, situação do pagamento e saldo em aberto), Pagamentos (conferir comprovantes Pix e registrar recebimentos no balcão: dinheiro, cartão na maquininha ou Pix), Solicitações (pedidos de horário) |
| Administrador | Tudo, alternando entre hotéis e hóspedes; registra devoluções |

Contas de demonstração (senha `Demo@1234`): `admin@hotelflex.test`, `recepcao.centro@hotelflex.test`, `recepcao.marazul@hotelflex.test`, `ana@hotelflex.test`, `bruno@hotelflex.test`.

## Como rodar (Laragon / WAMP / XAMPP)

1. Coloque a pasta em `www`, por exemplo `C:\laragon\www\hotelflex-direto`, e dê **Start All** no Laragon.
2. Crie o `.env`:

   ```bash
   copy .env.example .env
   ```

   Defina uma senha em `DB_PASSWORD`; o setup cria o usuário do banco com ela. `DB_ADMIN_PASSWORD` é a senha do **root do MySQL** (no Laragon, vazia), não a do site.
3. Crie o banco. Este comando **recria as tabelas e apaga os dados**:

   ```bash
   php bin/setup-database.php
   ```

4. Abra `http://localhost/hotelflex-direto/` (o app detecta sozinho a subpasta).
5. Para processar a fila de mensagens e expirar reservas não pagas:

   ```bash
   php bin/send-notifications.php
   ```

   Em produção, rode com `--loop` ou agende o comando.

Por padrão, e-mail e WhatsApp ficam em **modo teste**: as mensagens são salvas em `storage/outbox` (`.eml` e `.json`). Para envio real, use `MAIL_DRIVER=mail` (Laragon: Menu › Preferences › Mail Sender) e `WHATSAPP_DRIVER=cloud_api` com as credenciais da Meta e um modelo aprovado `atualizacao_reserva` com o texto `Olá, {{1}}! {{2}}: {{3}}`.

## Testes

```bash
composer install
```

```bash
composer test
```

São 45 testes unitários: preço, disponibilidade, cobranças, sinal, período de datas, filtros, cancelamento, WhatsApp e mensagens.

O teste de navegador cobre a jornada completa. Rode com o banco recém-criado:

```bash
npm install --no-save playwright && npx playwright install chromium
```

```bash
set BASE_URL=http://localhost/hotelflex-direto&& node tests/e2e/jornada-completa.js
```

## Segurança

- **Credenciais:** ficam só no `.env` (fora do git). O usuário do banco só tem `SELECT`, `INSERT` e `UPDATE`.
- **Consultas e telas:** prepared statements, CSRF em todo formulário, saída escapada e CSP sem scripts inline.
- **Comprovantes:** tipo conferido pelo conteúdo (PDF, PNG ou JPG, até 5 MB), nome aleatório e download só para o hóspede dono, a recepção do hotel ou o admin.
- **Permissões:** hóspede só vê as próprias reservas, recepção só o próprio hotel. Um id de outra conta responde "não encontrado".
- **Concorrência:** mudanças de status são condicionais (ninguém aprova o mesmo comprovante duas vezes), as reservas usam trava de linha contra reserva dupla e a fila usa `SKIP LOCKED`.
- **Valores:** sempre recalculados no servidor, em centavos.

## Formas de pagamento

| Forma | Como funciona |
|---|---|
| Pix com comprovante | O hóspede paga para a chave do hotel e envia o comprovante; a recepção confere. |
| No balcão (dinheiro, débito, crédito, Pix) | A recepção registra o recebimento na tela Pagamentos; fica salvo quem recebeu, quando e como. |

O **sinal** é sempre via Pix com comprovante, porque garante a reserva contra no-show. **Cartão online** (pago no site) ficou fora de propósito: exige um intermediador de pagamento (Mercado Pago, PagSeguro, Stripe), com taxas e regras de segurança de cartão (PCI), e iria contra a proposta de pagamento direto ao hotel. Cartões são aceitos na maquininha do próprio hotel, e o sistema nunca guarda dados de cartão.
