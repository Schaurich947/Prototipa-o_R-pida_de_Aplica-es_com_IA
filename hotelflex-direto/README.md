# HotelFlex — Plataforma de Hospedagem Flexível

Plataforma para vários hotéis: o hóspede reserva, entra mais cedo e sai mais tarde pagando por hora. O hotel libera quartos que ficaram prontos antes do horário.

- **Páginas:** HTML + PHP (templates em `views/`).
- **Backend:** PHP 8.2+ com MySQL/MariaDB via PDO e prepared statements.
- **Estilo:** Tailwind CSS compilado, com os tokens visuais do shadcn/ui.
- **JavaScript:** só para os sliders de horas, confirmações e atualização automática.

## Perfis e telas

| Perfil | Tela | O que faz |
|---|---|---|
| Hóspede | Minhas reservas | Reservas em todos os hotéis: em andamento, próximas e histórico |
| Hóspede | Detalhe da reserva | Pedir entrada antecipada/saída tardia, alterar datas, cancelar |
| Hóspede | Nova reserva | Buscar quartos livres por data e cidade e reservar |
| Recepção (do hotel) | Painel do dia | Chegadas de hoje (horário de entrada, liberar, check-in), hospedados (horário de saída, check-out) e limpeza |
| Recepção (do hotel) | Solicitações | Aprovar ou recusar pedidos dos hóspedes |
| Administrador | Tudo | Alterna entre hotéis e entre hóspedes |

O hóspede cria a própria conta em `/cadastro`. Contas de demonstração (senha `Demo@1234`): `admin@hotelflex.test`, `recepcao.centro@hotelflex.test`, `recepcao.marazul@hotelflex.test` e `ana@hotelflex.test`.

## Regras de horário

- Cada hotel define a entrada e a saída padrão, a janela máxima (ex.: 8h–18h), as horas de tolerância grátis na entrada e o tempo de limpeza entre hóspedes.
- O limite de horas antes da entrada depende de quem sai do mesmo quarto naquele dia. O limite depois da saída depende de quem chega. Sempre sobra o tempo de limpeza.
- A entrada antecipada cobra só as horas além da tolerância. A saída tardia cobra todas as horas.
- Ao alterar as datas, os horários flexíveis voltam ao padrão e os pedidos pendentes são cancelados.

## Como rodar

```bash
composer install
```

```bash
cp .env.example .env   # preencha usuário e senha do banco
```

```bash
php bin/setup-database.php   # cria banco e usuário e RECRIA as tabelas (apaga os dados) com a demonstração
```

```bash
composer serve   # http://localhost:8000
```

Testes e CSS:

```bash
composer test
```

```bash
npm install && npm run build:css
```

### Rodando no WAMP / XAMPP (subpasta do `www`)

O app descobre sozinho em qual pasta está, então `http://localhost/PROTO_IA_27.10/hotel-flex/` funciona sem editar nada.

1. No WAMP, ative **Apache → Módulos Apache → rewrite_module**.
2. Use os dois `.htaccess` do projeto (`hotel-flex/.htaccess` e `hotel-flex/public/.htaccess`) sem `RewriteBase`.
3. O `composer install` é opcional para rodar o app: sem a pasta `vendor/`, ele usa o carregador próprio (`src/autoload.php`). O Composer só é necessário para rodar os testes.
4. Crie o `.env` a partir do `.env.example`. No WAMP, o padrão é `DB_HOST=localhost`, `DB_ADMIN_USER=root` e `DB_ADMIN_PASSWORD=` vazio.
5. Com o MySQL ligado, rode `php bin/setup-database.php` dentro de `hotel-flex/`. Ele cria o banco, o usuário da aplicação (com a senha de `DB_PASSWORD`) e as tabelas com os dados de demonstração. Pode ser rodado de novo sem problema.

Se aparecer "Algo deu errado", veja o log do Apache (no WAMP, ícone → Apache → Log de erros do Apache). A causa real fica registrada lá com o prefixo `[hotelflex]`.

O usuário do banco usado pela aplicação (`DB_USER`) precisa apenas de `SELECT`, `INSERT` e `UPDATE`.
O `DB_ADMIN_USER` é usado só pelo script de setup.

## Estrutura

```
public/            index.php (front controller) e assets compilados
views/             páginas HTML/PHP: layouts/, pages/, partials/ (componentes pequenos)
src/Domain/        regras de negócio puras (preço, disponibilidade, status)
src/Repository/    acesso ao banco (PDO, prepared statements)
src/Service/       casos de uso e modelos de leitura das telas
src/Http/          rotas, sessão, CSRF, controllers
src/Bootstrap/     composição das dependências e tratamento de erros
database/          schema.sql e seed.sql
tests/             PHPUnit
```

## Segurança

- **Credenciais:** ficam só no `.env`, que está no `.gitignore`.
- **SQL:** só prepared statements nativos (`ATTR_EMULATE_PREPARES = false`).
- **XSS:** toda saída passa por `e()`, e o CSP bloqueia scripts inline.
- **Formulários:** CSRF em todo POST e validação no navegador e no servidor. O valor das horas é sempre recalculado no servidor.
- **Erros:** os detalhes vão só para o log do servidor; o usuário vê uma página genérica.
- **Login:** senhas com `password_hash`, mesma mensagem para e-mail inexistente e senha errada, e bloqueio temporário após 5 tentativas.
- **Permissões:** a recepção só acessa o próprio hotel, e o hóspede só acessa as próprias reservas. Um id de outra conta responde "não encontrado".
