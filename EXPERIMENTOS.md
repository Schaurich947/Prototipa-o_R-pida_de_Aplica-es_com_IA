# Experimentos de Otimização

Registro dos testes de otimização feitos neste projeto, conforme a seção 7 do `CLAUDE.md`.
Cada experimento muda **uma variável por vez**. Os mais recentes ficam no topo.

## Índice

| # | Data | Título | Métrica principal | Resultado |
|---|------|--------|-------------------|-----------|
| 001 | 2026-10-27 | MVP HotelFlex gerado com o CLAUDE.md | maior arquivo (linhas) | 111 linhas (limite 250) |

---

### EXP-001 — MVP HotelFlex gerado seguindo o CLAUDE.md

- **Data:** 2026-10-27
- **Status:** concluído (linha de base)
- **Área:** arquitetura | segurança | clean code
- **Hipótese:** com as diretrizes do `CLAUDE.md`, a IA gera código dentro dos limites de tamanho e com as regras de segurança aplicadas desde o início.
- **Mudança feita:** geração do MVP em `hotel-flex/` (HTML + PHP + MySQL).
- **Variável alterada:** nenhuma; esta é a medição inicial para comparar com as próximas.
- **Como medir:** `wc -l` nos arquivos, `composer test` e um roteiro Playwright (login nos 3 perfis, slider, liberação de quarto, aprovação, tentativa de burlar as horas e o CSRF).

| Métrica | Antes | Depois | Variação |
|---------|-------|--------|----------|
| Maior arquivo (linhas) | — | 111 | — |
| Arquivos PHP/JS | — | 84 | — |
| Testes unitários aprovados | — | 13/13 | — |
| Erros no console do navegador | — | 0 | — |
| CSS compilado (gzip) | — | 4,6 KB | — |
| Tentativas de burla aceitas (horas=99, POST sem CSRF, hóspede em rota do hotel) | — | 0 de 3 | — |

- **Conclusão:** as regras foram seguidas. O ponto fraco é o login, que ainda é só de demonstração.
- **Próximo passo:** repetir a mesma tarefa sem o `CLAUDE.md` e comparar as métricas.

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
