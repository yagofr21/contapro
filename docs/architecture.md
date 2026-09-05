# Arquitetura — Conta Pro

## Visão geral

Aplicação **monolítica modular** em Laravel 13. Separa-se o núcleo da plataforma
(identidade, autenticação) dos domínios de negócio (finanças, investimentos, dados de
mercado, dashboard, importação/exportação, notificações), organizados em módulos sob
`app/Modules/`.

```
                         ┌─────────────────────────────┐
   Navegador ──────────▶ │  FrankenPHP (Octane)         │
  (Inertia + Vue 3)      │  - workers persistentes      │
                         │  - HTTP/2, HTTP/3, HTTPS     │
                         └──────────────┬──────────────┘
                                        │
                              ┌─────────▼──────────┐
                              │  Rotas (web)       │
                              │  Middleware (auth, │
                              │  Inertia, verified)│
                              └─────────┬──────────┘
                                        │
                         ┌──────────────▼──────────────┐
                         │  Camada de Aplicação        │
                         │  Controllers (finos)        │
                         │  FormRequests · Policies    │
                         │  Actions · Services         │
                         └──────────────┬──────────────┘
                                        │
                ┌───────────────────────┼───────────────────────┐
                │                       │                       │
        ┌───────▼──────┐      ┌─────────▼────────┐   ┌─────────▼────────┐
        │ PostgreSQL   │      │ Redis (cache /   │   │ Queues / Jobs    │
        │ (fonte da    │      │ filas / sessions)│   │ - importação     │
        │ verdade)     │      │                  │   │ - cotações       │
        └──────────────┘      └──────────────────┘   └──────────────────┘
```

## Principais decisões

- **Monólito modular**: `app/Modules/{Identity,Finance,Investment,MarketData,Dashboard,ImportExport,Notification}`.
  Módulos internos com boundaries claros; controllers finos que delegam a Ações/Serviços.
- **Inertia + Vue 3 + TypeScript**: sem API REST para o SPA — o servidor responde
  props do Inertia diretamente às rotas web.
- **Controllers finos**: sem lógica de negócio; validação em `FormRequest`, autorização
  em `Policy`, operações em `Action`/`Service`, consultas em *Query Objects*/read models.
- **Value objects e enums PHP**: dinheiro representado por `Money` (ver
  `docs/financial-calculations.md`), tipos, status, direções etc. via enums.
- **Precisão monetária**: colunas `numeric(19,4)` para valores monetários; preços e
  quantidades de ativos `numeric(20,8)`. Cálculos feitos com `bcmath`, nunca com `float`.
- **Services de domínio**: ex. consolidar carteira, recalcular saldos, aplicar proventos.
- **Octane/FrankenPHP** como servidor de produção (worker mode), com fallback compatível
  com runtime convencional (`php artisan serve` em dev).
- **Fusos/idioma**: `America/Sao_Paulo`; `pt-BR` como locale principal.

## Camadas

1. **Transporte** — FrankenPHP/Octane + rotas web (Inertia) e worker CLI.
2. **Apresentação (SPA)** — páginas `.vue` em `resources/js/Pages/**`, layout base,
   componentes reutilizáveis, tema claro/escuro.
3. **Aplicação** — controllers, FormRequests, Policies, Actions/Services, Query Objects.
4. **Domínio** — modelos Eloquent, enums, value objects, regras de negócio.
5. **Persistência** — PostgreSQL (migrations), cache/filas/sessões em Redis.

## Health check

`GET /up` dispara o evento `Illuminate\Foundation\Events\DiagnosingHealth`. O listener
registrado em `App\Providers\AppServiceProvider` valida conexão com o banco e o cache,
retornando `200 {"status":"up"}` quando tudo está saudável (ou `500` caso contrário).

## Qualidade

- **Pest** para testes de feature/unit.
- **PHPStan (Larastan)** nível 6.
- **Pint** para formatação.
- **ESLint + vue-tsc** no frontend (CI equivalente ao backend).

## Fluxo de desenvolvimento

1. Sobe a stack (`docker compose up -d --build`).
2. Servidor HTTP de dev em `http://localhost:8000` (app) e servidor prod-like em
   `https://localhost` (frankenphp).
3. Vite com hot reload pelo serviço `node` (`npm run dev`).

## Modulo Finance (Fase 2)

O modulo financeiro usa o fluxo `Route -> Controller -> FormRequest -> Policy ->
Action -> Model`. Todas as consultas partem do usuario autenticado. Transferencias
sao gravadas em transacao de banco como dois lancamentos ligados pelo mesmo UUID
`transfer_id`; atualizacoes e exclusoes sempre afetam as duas pontas.

O dashboard usa `AccountSummaryQuery` para consolidar saldo inicial, receitas,
despesas e transferencias sem armazenar saldo derivado. Orcamentos comparam o limite
da categoria ao total de despesas dentro do periodo mensal, anual ou personalizado.
