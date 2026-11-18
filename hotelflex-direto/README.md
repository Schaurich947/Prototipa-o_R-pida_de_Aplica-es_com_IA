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
composer test               # unitários (regras puras)
composer test:integration   # integração, segurança e concorrência (banco de teste próprio)
composer test:volume        # volume: milhares de registros e tempos
```

Os testes não mexem no banco do sistema: usam `<DB_NAME>_test`, recriado a cada teste. Detalhes e objetivo de cada grupo em [`tests/README.md`](tests/README.md).

## Notas fiscais (fase 1: emissão simulada)

> **Simulação.** Nenhuma nota é enviada à prefeitura ou à SEFAZ. Toda nota e todo XML saem marcados "SIMULAÇÃO · Documento de teste · sem valor fiscal". Alíquotas e regras devem ser confirmadas com o contador de cada hotel.

| Venda | Nota | Tributo | Quando entra na fila |
|---|---|---|---|
| Hospedagem (diárias, horas extras, diferenças) | NFS-e (serviço, LC 116 item 09.01) | ISS | No **check-out**, com o valor final (pago − créditos) |
| Café avulso e venda na hora | NFC-e (consumidor) | ICMS | Quando o café é **servido** |

- **Antes de pagar**, o cliente escolhe em nome de quem sai a nota: ele mesmo (CPF), outra pessoa (CPF), uma empresa (CNPJ) ou estrangeiro (passaporte e país). **Sem CPF, CNPJ ou documento estrangeiro não há reserva nem café**: o hotel é obrigado a emitir a nota. Na venda na hora, a recepção digita o CPF ou CNPJ. Os cadastros ficam em **Dados para nota fiscal**.
- **Inscrição estadual:** só empresa tomadora pode ter (número ou ISENTO); para CPF o campo é recusado. A inscrição estadual do **hotel** é outra coisa: a SEFAZ exige para o hotel emitir a NFC-e do café.
- **CNPJ:** validação dos dígitos (inclusive o CNPJ alfanumérico de julho de 2026) e consulta da situação na Receita. Razão social e endereço vêm da consulta, e empresa baixada ou inapta não pode ser tomadora. `CNPJ_LOOKUP_DRIVER=simulacao` (offline) ou `brasilapi` (dados abertos da Receita, gratuito).
- **ISS retido:** o hotel escolhe a regra (nunca, ou empresas do mesmo município, exceto Simples e MEI).
- **Reforma tributária:** CBS 0,9% e IBS 0,1% informativos (ano de teste de 2026), em uma tabela de regras com data de vigência. Na hospedagem e na alimentação, a empresa tomadora **não** aproveita crédito (regimes específicos da LC 214/2025).
- **Fila de emissão:** numeração sequencial por hotel, nova tentativa automática se o emissor cair, e **consulta antes de reenviar** (se a resposta se perdeu, a nota não sai em dobro). Rode `php bin/send-notifications.php` ou use **Emitir notas da fila** na tela **Notas fiscais**.
- **Pré-controle:** cada nota autorizada lança ISS, ICMS, CBS e IBS por natureza (a recolher, retido, informativo); o cancelamento (motivo com 15 ou mais caracteres) lança o estorno. Não é a apuração oficial.
- **PDF da nota:** DANFSe (A4, layout da NFS-e Nacional) para hospedagem e DANFE NFC-e (cupom de 80 mm) para o café, com QR code que abre a nota no sistema. Ambos levam "EMITIDA EM AMBIENTE DE SIMULAÇÃO (HOMOLOGAÇÃO) – SEM VALOR FISCAL" e a marca d'água SIMULAÇÃO. Gerados no servidor com Dompdf (rode `composer install` depois de atualizar). NFS-e e NFC-e têm numerações separadas.
- **Telas:** hóspede (Dados para nota fiscal, nota na reserva e no café), recepção (Notas fiscais, Dados fiscais só para leitura) e administrador (edita os dados fiscais do hotel).

Próximas fases: emissão real (`FISCAL_DRIVER=nacional` pela API da NFS-e Nacional, ou por um intermediador) com certificado digital, emissor alternativo, notas de compra e créditos, perfil Contador e relatórios.

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

## Descontos por forma de pagamento

| Forma | Desconto nas diárias |
|---|---|
| Pix à vista: 100% na reserva | 5% |
| 50% via Pix + restante via Pix até 24h antes da entrada | 2% (indisponível se a entrada for muito próxima) |
| 50% via Pix + restante no hotel | sem desconto |

O desconto vale só para as diárias. Horas extras e diferenças pedidas depois são cobradas sem desconto.

## Café da manhã avulso

Qualquer cliente cadastrado, hospedado ou não, pode reservar o café da manhã de um hotel em **Café da manhã**. Escolhe hotel, data e número de pessoas e paga:

- **via Pix antecipado:** 5% de desconto, com comprovante; sem comprovante no prazo, os lugares são liberados;
- **no hotel:** sem desconto, em dinheiro, cartão ou Pix no balcão.

Cada hotel define o preço por pessoa, o horário e o limite de lugares por dia.

**Hóspedes têm prioridade:** como a reserva não guarda quantas pessoas há no quarto, cada hotel define uma estimativa de lugares por quarto (ex.: 2). Para cada data, o sistema conta os quartos com hóspede naquela manhã (quem dormiu no hotel na noite anterior, inclusive no dia da saída):

```
lugares livres = capacidade − quartos com hóspede × lugares por quarto − cafés avulsos já reservados
```

**Venda na hora, sem reserva:** cada hotel define um preço de balcão, maior que o normal (ex.: R$ 55 contra R$ 45 reservando, ou R$ 42,75 com Pix antecipado). Os clientes veem esse aviso na tela do café. A recepção registra a venda em **Café da manhã**, só durante o horário do café e só se houver lugar livre. A reserva usa trava no banco, então o limite nunca é ultrapassado. Na tela **Café da manhã** do hotel, a recepção vê o dia, confere comprovantes, registra recebimentos no balcão e marca quem foi atendido.
