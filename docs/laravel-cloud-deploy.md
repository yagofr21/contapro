# Deploy no Laravel Cloud — Conta Pro

Guia operacional de deploy quando o projeto for para produção (Fase 6).

## Pré-requisitos

- Projeto já versionado no GitHub.
- Conta no Laravel Cloud com billing habilitado.
- Provedor de banco/valstate configurado (Postgres e Valkey/Redis) — ver seção
  "Recursos".

## 1. Repositório e conexão

1. Publicar o repositorio no GitHub somente apos o workflow `CI` passar.
2. No Laravel Cloud, "New project" → conectar o repo.
3. Selecionar a branch de produção (ex.: `main`).

## 2. Recursos e variáveis

Criar:

- **Postgres** (recurso padrão do Laravel Cloud) — driver `pgsql`.
- **Valkey/Redis** — driver `redis` para cache/queues/sessions.

Preencher variáveis de ambiente (valores iguais aos de produção):

```
APP_ENV=production
APP_KEY=<gerada>
APP_DEBUG=false
APP_URL=<https://seu-dominio>
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR
APP_TIMEZONE=America/Sao_Paulo

DB_CONNECTION=pgsql
DB_HOST=<host do recurso>
DB_DATABASE=<db>
DB_USERNAME=<user>
DB_PASSWORD=<senha>

SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=redis
CACHE_STORE=redis
APP_MAINTENANCE_DRIVER=cache
APP_MAINTENANCE_STORE=redis
REDIS_HOST=<host valkey>
REDIS_PORT=6379

LOG_CHANNEL=stack
LOG_LEVEL=warning

BRAPI_TOKEN=<token para painel de investimentos>

MAIL_MAILER=resend
RESEND_API_KEY=<secret>
MAIL_FROM_ADDRESS=<remetente verificado>
MAIL_FROM_NAME="Conta Pro"
```

> Não é necessário configurar `OCTANE_SERVER` — o Laravel Cloud usa o Cloud Runtime,
> equivalente ao FrankenPHP/Octane upstream (workers persistentes, HTTP/2/3, TLS).

## 3. Build e deploy

Comando de build:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction && npm ci && npm run build && php artisan optimize
```

Comando de deploy:

```bash
php artisan migrate --force && php artisan investments:rebuild-accounting
```

`APP_KEY`, `BRAPI_TOKEN` e credenciais de e-mail devem viver no Secrets Manager e
nunca no repositorio ou no comando de build.

## 4. Assets

O Cloud Runtime compila Vite se o processo de build estiver habilitado no console
("Build" passo ativo). Alternativamente, eject o build no commit. Em ambos os casos
`npm ci && npm run build` deve ser executado em ambiente node.

## 5. Health check

Use `/up` como liveness check barato. Use `/ready` para verificar banco e cache durante
smoke tests e diagnosticos, sem expor mensagens internas.

## 6. Pós-deploy

- Confirmar sessões/cache via Valkey (não usar drivers `array`/`file` em produção).
- Criar um worker Redis explicito com
  `php artisan queue:work redis --queue=default --sleep=3 --tries=25 --timeout=60`.
- Habilitar o Scheduler no painel e confirmar `market-data:sync` em
  `php artisan schedule:list`.
- Confirmar que a fila drena e revisar `failed_jobs`/logs apos uma atualizacao manual.
- Nao selecionar Managed Queue sem antes adicionar/configurar o AWS SDK exigido por
  esse produto. Este projeto usa worker Redis.

## 7. Rollback

Reverter um deploy pelo console ("Deployments" → rollback para revisão anterior).
Migrações destrutivas devem ser revisadas antes do rollback.

## 8. Validacao pos-deploy

```bash
php artisan about
php artisan migrate:status
php artisan schedule:list
php artisan config:show session
php artisan config:show queue
```

Confirmar envio real de verificacao de e-mail e recuperacao de senha, cookies com
`Secure`/`HttpOnly`/`SameSite=Lax`, headers de seguranca, backup/PITR do Postgres,
alertas de erro e um rollback ensaiado em staging.
