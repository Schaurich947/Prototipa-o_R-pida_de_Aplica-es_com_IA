# HotelFlex — MVP de Hotelaria Flexível

Entrada antecipada quando o quarto fica pronto antes das 14h e late check-out cobrado por hora.

- **Páginas:** HTML + PHP (templates em `views/`).
- **Backend:** PHP 8.2+ com MySQL/MariaDB via PDO e prepared statements.
- **Estilo:** Tailwind CSS compilado, com os tokens visuais do shadcn/ui.
- **JavaScript:** só para o slider de horas e a atualização automática das telas.

## Telas

| Perfil | Tela | Rota |
|---|---|---|
| Recepção / Governança | Painel de Liberação e Horas Adicionais | `/hotel/liberacao` |
| Recepção / Governança | Solicitações dos hóspedes | `/hotel/solicitacoes` |
| Hóspede | Minha reserva (dados + faturamento) | `/hospede/reserva` |
| Hóspede | Disponibilidade (entrada antecipada e late check-out) | `/hospede/disponibilidade` |
| Administrador | Todas as telas acima, com seletor de hóspede | — |

## Como rodar

```bash
composer install
```

```bash
cp .env.example .env   # preencha usuário e senha do banco
```

```bash
php bin/setup-database.php   # cria banco, usuário da aplicação, tabelas e dados de demonstração
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
- **Perfis:** cada rota tem perfis permitidos. O hóspede só acessa a própria reserva, mesmo que altere o formulário.
- **Erros:** os detalhes vão só para o log do servidor; o usuário vê uma página genérica.
- **Limitação do MVP:** o login é de demonstração (escolha de perfil). Antes de produção, ele precisa de autenticação real.
