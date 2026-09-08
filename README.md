# Conta Pro — Sistema de Gestão Financeira Pessoal e Carteira de Investimentos

Sistema web multiusuário em **pt-BR** para gestão de finanças pessoais e carteira de
investimentos. Desenvolvido com PHP 8.5, Laravel 13, PostgreSQL, Inertia + Vue 3
(Composition API, `<script setup>`, TypeScript), Tailwind CSS 4, ECharts e
Octane/FrankenPHP, com deploy alvo em Laravel Cloud.

> **Status atual:** Fases 0 a 5, 7, 8, 9, 10 e 11 concluídas; Fase 6 preparada no repositorio e
> aguardando apenas o provisionamento externo no Laravel Cloud.
> Carteiras, catalogo de ativos, compras, vendas, proventos, posicoes e custo medio
> possuem policies, validacao, actions transacionais, telas responsivas e testes
> multiusuario. A integração brapi.dev atualiza cotações e histórico por filas com
> cache, retries, rate limit e sincronização agendada. Dashboard e relatorios mantem
> moedas isoladas, exibem graficos ECharts e exportam lancamentos em CSV.
> Importacoes CSV financeiras e de investimentos possuem previa por linha,
> deteccao de duplicidades, confirmacao atomica e exports round-trip.
> Recorrencias e parcelas materializam lancamentos via scheduler, e a agenda projeta
> os proximos 60 dias com saldo previsto por conta (Fase 9).
> Metas financeiras definem valor, moeda, prazo e contas e mostram progresso pelo
> saldo corrente (Fase 10).
> Conciliacao de extrato compara o declarado com o detectado por regras de
> casamento (exata ou janela de 3 dias) e fixa o status por saldo e linhas (Fase 11).
> A Fase 12 (alertas por e-mail) esta adiada por falta de servidor SMTP.
> Headers, health/readiness, CI PostgreSQL e guia operacional
> preparam o deploy; secrets e recursos Cloud ainda devem ser configurados no painel.

---

## Tecnologias

| Camada | Stack |
| --- | --- |
| Backend | PHP 8.5, Laravel 13, PostgreSQL 17, Redis |
| Frontend | Inertia v3, Vue 3 (Composition API + `<script setup>`), TypeScript, Tailwind CSS 4, Vite 8 |
| Gráficos | ECharts 6 + vue-echarts |
| Servidor | Laravel Octane + FrankenPHP (workers persistentes, HTTP/2/3, HTTPS) |
| Testes/qualidade | Pest, PHPStan (Larastan), Pint, ESLint, vue-tsc |
| Deploy | Laravel Cloud (Cloud Runtime, Postgres, Valkey/Redis) |

## Pré-requisitos

- Docker + Docker Compose (a stack roda inteiramente em containers).

## Subindo o ambiente (Docker Compose)

```bash
# Sobe a aplicação (Postgres, Redis, PHP, filas, scheduler e FrankenPHP).
docker compose up -d --build

# Instala dependências do frontend e builda os assets (serviço `node`).
docker compose exec node npm install
docker compose exec node npm run build   # ou: npm run dev (hot reload)

# Aplicação:
#   - app (dev):      http://localhost:8000   (php artisan serve)
#   - frankenphp:     https://localhost       (Octane + FrankenPHP, HTTP/2/3)
# Health check:       https://localhost/up     ou  http://localhost:8000/up
```

O certificado HTTPS em `https://localhost` é **auto-assinado** (Caddy `tls internal`);
aceite o aviso de segurança do navegador na primeira visita.

### Executando comandos no container

```bash
# Backend (PHP/Artisan/Composer/Pest)
docker compose exec app bash -lc "php artisan migrate"
docker compose exec app vendor/bin/pest
docker compose exec app vendor/bin/phpstan analyse
docker compose exec app vendor/bin/pint

# Frontend
docker compose exec node npm run lint
docker compose exec node npm run type-check
docker compose exec node npm run build
```

## Configuração

Copie o `.env.example` para `.env` e ajuste as credenciais:

```bash
cp .env.example .env
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

> O Docker Compose define apenas o *wiring* de rede (`DB_HOST`, `REDIS_HOST`, portas).
> As demais variáveis (`APP_ENV`, `DB_DATABASE`, `CACHE_STORE`, etc.) vivem no `.env`
> do projeto — injetá-las no compose contaminaria `$_SERVER` e impediria o PHPUnit
> de sobrescrevê-las durante os testes.

## Estrutura do projeto

```
app/
  Modules/           # Módulos de domínio (Identity, Finance, Investment, ...)
  Actions/           # Ações / casos de uso reutilizáveis
  Services/          # Serviços de aplicação
  Enums/             # Enums PHP
  ValueObjects/      # Money, etc.
database/
  migrations/
docker/              # Dockerfile(s) e entrypoints dos serviços
frankenphp/          # Caddyfile do servidor FrankenPHP
compose.yaml         # Serviços: app, db, cache, node, frankenphp
docs/                # Arquitetura, modelo de dados, cálculos, deploy, segurança
```

## Scripts de qualidade

```bash
composer test           # Pest (testes de feature + unit)
composer phpstan        # PHPStan (nível 6) + Larastan
composer format         # Pint (aplica formatação)
composer format:test    # Pint (apenas verifica)
npm run lint            # ESLint (Vue + TypeScript)
npm run type-check      # vue-tsc --noEmit
npm run build           # vue-tsc + vite build
```

## Documentação

- [Arquitetura](docs/architecture.md)
- [Modelo de dados (ER)](docs/data-model.md)
- [Cálculos financeiros](docs/financial-calculations.md)
- [Dados de mercado](docs/market-data.md)
- [Recorrências, parcelas, agenda e projeção](docs/recurring-agenda.md)
- [Metas financeiras](docs/financial-goals.md)
- [Conciliacao de extrato](docs/reconciliation.md)
- [Relatorios e agregacoes](docs/reports.md)
- [Importacoes CSV](docs/imports.md)
- [Deploy no Laravel Cloud](docs/laravel-cloud-deploy.md)
- [Segurança](docs/security.md)

## Roteiro (Fases)

1. **Fase 0 — Fundação** (concluída): infra, app no ar, stack frontend, auth, qualidade, Octane, health check, docs base.
2. **Fase 1 — Modelagem** (concluída): migrations de domínio, ER, enums, modelos, factories, seeders e testes de integridade.
3. **Fase 2 — Identidade & Finanças** (concluída): perfis, contas, categorias, transações, transferências, orçamentos e dashboard financeiro.
4. **Fase 3 — Investimentos** (concluída): ativos, carteiras, compras, vendas, proventos, posições e custo médio.
5. **Fase 4 — Cotações & Mercado** (concluída): integração brapi.dev, cache, histórico, atualização manual e agendada.
6. **Fase 5 — Dashboard & Relatórios** (concluída): ECharts, agregações por moeda e exportação CSV.
7. **Fase 6 — Produção** (preparada): hardenização, CI, health/readiness e runbook Cloud; provisionamento depende do painel Laravel Cloud.
8. **Fase 7 — Investimentos Avançados** (concluída): corretoras, desdobramentos e grupamentos, resultado realizado, replay contábil e retorno total.
9. **Fase 8 — Importacao e conciliacao** (concluída): CSV financeiro e de investimentos, previa validada, duplicidades, confirmacao atomica e exportacao round-trip.
10. **Fase 9 — Recorrencias, parcelas, agenda e projecao** (concluída): schedulers `recurring:generate`/`installments:process`, agenda de 60 dias e saldo projetado por conta. Ver `docs/recurring-agenda.md`.
11. **Fase 10 — Metas financeiras** (concluída): valor, moeda, prazo e contas com progresso pelo saldo corrente. Ver `docs/financial-goals.md`.
12. **Fase 11 — Conciliação avançada** (concluída): extrato declarado × detectado por regras exatas ou janela de 3 dias, saldo e divergências de linhas com status `reconciled`/`divergent`. Ver `docs/reconciliation.md`.
13. **Fase 12 — Alertas** (adiada): notificações por e-mail/webhook para saldo, orçamento e metas. Suspensa por ausência de servidor SMTP; será retomada quando houver um.
14. **Fase 13 — Bot WhatsApp** (aguardando): integração via WhatsApp Cloud API (Meta).
