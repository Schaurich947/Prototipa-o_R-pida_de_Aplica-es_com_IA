# Experimentos de Otimização

Registro dos testes de otimização feitos neste projeto, conforme a seção 7 do `CLAUDE.md`.
Cada experimento muda **uma variável por vez**. Os mais recentes ficam no topo.

## Índice

| # | Data | Título | Métrica principal | Resultado |
|---|------|--------|-------------------|-----------|
| 002 | 2026-11-10 | HotelFlex Direto: unificar dois apps | código reaproveitado | ~85% (15% novo) |
| 001 | 2026-10-27 | MVP HotelFlex gerado com o CLAUDE.md | maior arquivo (linhas) | 111 linhas (limite 250); registro no branch feat/hotel-flex |

---

### EXP-002 — Unificar HotelFlex e Comprova em um novo sistema

- **Data:** 2026-11-10
- **Status:** concluído
- **Área:** arquitetura
- **Hipótese:** se os apps seguem as mesmas diretrizes (SRP, camadas separadas, injeção de dependências), dá para montar um sistema novo combinando os módulos, com pouco código novo e em poucas horas.
- **Mudança feita:** `hotelflex-direto/` = reservas do HotelFlex + comprovante e fila de mensagens do Comprova + ciclo de pagamento Pix.
- **Variável alterada:** reaproveitar módulos prontos em vez de escrever do zero.
- **Como medir:** comparação arquivo a arquivo (PHP, JS, SQL) com os dois projetos de origem; `composer test`; teste de navegador da jornada (`tests/e2e/jornada-completa.js`).

| Métrica | Antes | Depois | Variação |
|---------|-------|--------|----------|
| Linhas iguais ao HotelFlex | — | 32% | — |
| Linhas adaptadas do HotelFlex | — | 37% | — |
| Linhas vindas do Comprova | — | 13% | — |
| Linhas novas | — | 15% | — |
| Maior arquivo (linhas) | 140 (HotelFlex) | 183 | +43, dentro do limite de 250 |
| Testes unitários | 18 | 33 | +15 |
| Erros de console na jornada completa | — | 0 | — |
| Tempo de desenvolvimento | — | cerca de 2h30 | — |

- **Conclusão:** a hipótese se confirmou. As camadas separadas permitiram plugar módulos inteiros (canais de envio, fila, armazenamento de comprovantes) sem reescrever. Quase todo o esforço foi nas regras novas de pagamento. O teste de navegador encontrou três bugs de integração (upload que não chegava ao controller, chegadas não pagas fora do painel e descrição de cobrança desatualizada), que foram corrigidos antes da entrega.
- **Próximo passo:** extrair as partes comuns (Http, View, Notification) para uma biblioteca compartilhada, em vez de copiar entre projetos.

---

## Modelo (copie para cada novo experimento)

### EXP-000 — Título curto

- **Data:** AAAA-MM-DD
- **Status:** planejado | em andamento | concluído | descartado
- **Área:** arquitetura | performance | segurança | clean code | prompt/IA
- **Hipótese:** se fizermos X, então Y melhora, porque Z.
- **Mudança feita:** descrição objetiva (arquivos, commit ou PR).
- **Variável alterada:** a única coisa que mudou.
- **Como medir:** ferramenta, comando e condições (ex.: Lighthouse mobile, 3 execuções, média).

| Métrica | Antes | Depois | Variação |
|---------|-------|--------|----------|
| ex.: tempo de carregamento (ms) | | | |

- **Conclusão:** a hipótese se confirmou? Manter, ajustar ou reverter?
- **Próximo passo:** o que testar em seguida.
