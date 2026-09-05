# Segurança — Conta Pro

## Autenticação e sessão

- **Breeze (Laravel 13)** com email+senha (web), CSRF habilitado por padrão.
- Senhas com `bcrypt` (`BCRYPT_ROUNDS=12`).
- Verificação de e-mail implementada (`User` recebe `Notification` de verificação).
- Sessões em **Redis** (`SESSION_DRIVER=redis`), com cookie `Secure`, `HttpOnly` e
  `SameSite=Lax`. O payload permanece no servidor.
- HTTPS forçado em produção (`APP_URL` https + middleware trust proxies conforme
  proxy do Laravel Cloud).
- Bloqueio de brute-force em login via throttle nativo.
- Registro, recuperacao e confirmacao de senha possuem throttles dedicados.

## Dados sensíveis

- `.env` **nunca** versionado; `.env.example` sem segredos.
- `APP_KEY` obrigatória (`php artisan key:generate`).
- Nunca logar senhas, tokens (ex.: `BRAPI_TOKEN`), chaves de API, cookies nem dados
  PII em excesso. `LOG_LEVEL` em produção: `warning`.
- Valor monetário como `string`/`numeric`, nunca `float`, para evitar erros de
  precisão além de qualquer issue de arredondamento.

## Autorização

- TODAS as rotas de recursos exigem autenticação (`auth`) + verificação de email
  (`verified` em rota relevante).
- **Polícies** (`App\Policies\*`) em cada recurso — checagem por `user_id` do dono
  (e, quando aplicável, ownership indireto por `portfolio->user_id`).
- Regra de escopo: consultas sempre filtradas por `user_id` (jamais por ID global).

## Entrada de usuário

- Validação em **FormRequest** (mensagens em pt-BR via validação do Laravel com
  `APP_LOCALE=pt_BR`).
- `XSS`: escape automático do Blade/Inertia; atributos dinâmicos sanitizados no Vue.
- Exportacoes CSV sao transmitidas sem armazenamento persistente e neutralizam
  formulas de planilha.

## Infraestrutura

- Servidor: FrankenPHP (Octane) com HTTPS; comportamento prod-like em `https://localhost`.
- E-mail: `log` em dev; em produção configurar provedor SMTP (ex.: Resend/Mailgun)
  com credenciais no `.env` do Laravel Cloud.
- Filas: Redis, com janela de retry, backoff limitado e log estruturado de falha final.
- Não usar `APP_DEBUG=true` em produção.

## Headers e disponibilidade

- Respostas web incluem CSP, `nosniff`, bloqueio de frames, politica de referencia e
  restricao de camera/microfone/geolocalizacao.
- HSTS e habilitado quando `APP_ENV=production`.
- `/up` e um liveness check sem dependencias; `/ready` verifica banco e cache.

## Check-list antes da produção

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` https
- [ ] `APP_KEY` estavel armazenada no Secrets Manager
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] Provedor de e-mail e dominio remetente validados
- [ ] Worker Redis e Scheduler habilitados no Laravel Cloud
- [ ] CI e migration PostgreSQL aprovadas antes do deploy
- [ ] Alertas, backups e rollback testados em staging
- [ ] Sem segredos no controle de versao
