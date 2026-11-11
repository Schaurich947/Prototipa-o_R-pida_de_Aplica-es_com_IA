# Testes do HotelFlex Direto

Os testes ficam fora do sistema e **não mudam o código**. Os testes de integração e de volume usam um **banco só deles**, `<DB_NAME>_test` (ex.: `hotelflex_direto_test`), recriado do zero a cada teste. O banco do sistema nunca é tocado, e a base se recusa a rodar se o nome não terminar em `_test`.

## Como rodar

Pré-requisitos: `composer install`, o `.env` configurado e o MySQL ligado (Laragon: Start All).

```bash
composer test               # unitários: regras puras, sem banco (segundos)
composer test:integration   # integração: serviços reais + banco de teste
composer test:volume        # volume: gera milhares de registros e mede tempos
composer test:all           # unitários + integração
```

O retorno lista cada comportamento esperado com ✔ (comportou-se como planejado) ou ✘ (não se comportou), e o motivo da falha:

```
Datas e conflitos de reserva
 ✔ Encaixe no mesmo dia: um sai às 12h e o próximo entra às 14h
 ✘ Reserva que fica dentro de outra é bloqueada
   │ Esperava "O quarto não está disponível nessas datas.", mas a reserva foi criada
```

## O que cada grupo verifica

| Grupo | Natureza | Objetivo |
|---|---|---|
| `tests/Domain`, `tests/Service` | Unitário | Regras de preço, desconto, limites de horário, cancelamento, café, mensagens |
| `DateConflictsTest` | Integração | Nunca duas estadias no mesmo quarto ao mesmo tempo (horas extras e 1h de limpeza incluídas) |
| `RoomAvailabilityTest` | Integração | Hotel lotado, último quarto, reserva não paga segurando o quarto, lotação do café com hóspedes |
| `ChargesTest` | Integração | Dinheiro certo até o centavo: desconto, sinal, diferenças, créditos, cancelamento, balcão |
| `DeadlinesTest` | Integração | Limites exatos de tempo (1 min antes/depois): prazo do Pix, cancelamento, meia-noite, horário do café |
| `SecurityTest` | Integração | Permissões entre hóspedes e hotéis, upload falso, CSRF, bloqueio de login |
| `ConcurrencyTest` | Integração | Ações simultâneas de verdade (processos paralelos): reserva dupla, aprovação dupla, lotação, fila |
| `tests/Performance` | Volume | Tempo das telas principais com dezenas de milhares de registros |

O relógio é controlado (`TestClock`): os testes "viajam" para prazos, meia-noite e horário do café sem esperar o tempo passar.

## Bugs que estes testes já encontraram (e foram corrigidos)

1. **Reserva para a mesma noite** (chegando depois das 14h) não aceitava "sinal + restante no hotel". Encontrado por `DeadlinesTest`.
2. **Deadlock no café** com reservas simultâneas: 3 de 4 clientes recebiam erro. Encontrado por `ConcurrencyTest`. Corrigido com trava por hotel e nova tentativa automática de transações.

## Planejado: testes de navegador

Hoje existe uma jornada única em `tests/e2e/jornada-completa.js` (Playwright). O plano é dividi-la em testes curtos e independentes (reserva, pagamento, recepção, café, perfil), para que um passo quebrado não esconda os outros, com dados próprios e horário controlado.
