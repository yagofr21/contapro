# Modelo de dados — Conta Pro

> **Nota de status:** modelo implementado na **Fase 1** pelas migrations de domínio,
> validado em PostgreSQL 17 e SQLite em memória nos testes automatizados.

## Convenções

- **Precisão monetária:** valores em dinheiro → `numeric(19,4)` (4 casas decimais).
- **Preços/quantidades de ativos:** `numeric(20,8)`.
- **Moeda:** ISO 4217 (ex.: `BRL`), `varchar(3)`.
- **Soft deletes** onde fizer sentido (contas, categorias, ativos, carteiras).
- **Timestamps** com `created_at`/`updated_at` e, quando relevante, `deleted_at`.
- **Fuso:** `America/Sao_Paulo`; datas como `date`, eventos com hora como `datetime`.

## Diagrama ER (Mermaid)

```mermaid
erDiagram
    USERS {
        bigint id PK
        varchar name
        varchar email UK
        timestamp email_verified_at
        text password
        varchar locale "default pt_BR"
        varchar timezone "America/Sao_Paulo"
    }

    FINANCIAL_ACCOUNTS {
        bigint id PK
        bigint user_id FK
        varchar name
        varchar type "checking|savings|cash|credit_card|investment"
        char(3) currency "BRL"
        numeric(19,4) initial_balance
        boolean is_archived
        timestamp deleted_at
        timestamps
    }

    CATEGORIES {
        bigint id PK
        bigint user_id FK
        bigint parent_id FK "nullable"
        varchar name
        varchar type "income|expense"
        varchar color
        timestamp deleted_at
        timestamps
    }

    TRANSACTIONS {
        bigint id PK
        bigint user_id FK
        bigint account_id FK
        bigint category_id FK "nullable"
        uuid transfer_id "nullable"
        varchar type "income|expense|transfer_in|transfer_out"
        numeric(19,4) amount
        date transaction_date
        text description
        timestamps
    }

    BUDGETS {
        bigint id PK
        bigint user_id FK
        bigint category_id FK
        numeric(19,4) limit_amount
        varchar period "monthly|yearly|custom"
        date starts_on
        date ends_on "nullable"
        timestamps
    }

    ASSETS {
        bigint id PK
        varchar symbol
        varchar name
        varchar type "stock|fii|etf|bond|reit|crypto"
        varchar market "B3|NASDAQ|NYSE|CRYPTO"
        varchar currency "BRL|USD"
        boolean is_active
        timestamp deleted_at
        timestamps
    }

    PORTFOLIOS {
        bigint id PK
        bigint user_id FK
        varchar name
        varchar currency
        timestamp deleted_at
        timestamps
    }

    BROKERS {
        bigint id PK
        bigint user_id FK
        varchar name
        boolean is_active
        timestamp deleted_at
        timestamps
    }

    PORTFOLIO_HOLDINGS {
        bigint id PK
        bigint portfolio_id FK
        bigint asset_id FK
        bigint broker_id FK "nullable"
        numeric(20,8) quantity
        numeric(20,8) average_cost "per unit"
        timestamps
    }

    ASSET_TRANSACTIONS {
        bigint id PK
        bigint portfolio_id FK
        bigint asset_id FK
        varchar type "buy|sell|split|dividend|interest"
        numeric(20,8) quantity
        numeric(20,8) unit_price
        numeric(19,4) fees
        numeric(20,8) split_from "nullable"
        numeric(20,8) split_to "nullable"
        numeric(19,4) gross_amount "nullable; venda/provento"
        numeric(19,4) net_amount "nullable; venda/provento"
        numeric(19,4) realized_cost_basis "nullable; venda"
        numeric(19,4) realized_profit_loss "nullable; venda"
        date transaction_date
        text note
        timestamps
    }

    PRICE_HISTORY {
        bigint id PK
        bigint asset_id FK
        date price_date
        numeric(20,8) open
        numeric(20,8) high
        numeric(20,8) low
        numeric(20,8) close
        numeric(20,8) adjusted_close
        bigint volume
    }

    IMPORT_BATCHES {
        bigint id PK
        uuid public_id UK
        bigint user_id FK
        bigint portfolio_id FK "nullable"
        varchar kind "financial|investment"
        varchar status "previewed|confirmed|failed"
        varchar original_filename
        char file_sha256
        json summary
        timestamp confirmed_at "nullable"
        timestamps
    }

    IMPORT_ROWS {
        bigint id PK
        bigint import_batch_id FK
        integer row_number
        json raw
        json normalized "nullable"
        char fingerprint "nullable"
        varchar status "valid|invalid|duplicate|imported"
        json errors "nullable"
        varchar importable_type "nullable"
        bigint importable_id "nullable"
        timestamps
    }

    TRANSACTION_SCHEDULES {
        bigint id PK
        bigint user_id FK
        bigint account_id FK
        bigint destination_account_id FK "nullable; transfer"
        bigint category_id FK "nullable"
        varchar type "income|expense|transfer"
        numeric(19,4) amount
        varchar frequency "daily|weekly|monthly|yearly"
        date starts_on
        date ends_on "nullable"
        date next_run_date
        text description "nullable"
        boolean is_active
        timestamps
    }

    INSTALLMENTS {
        bigint id PK
        bigint user_id FK
        bigint account_id FK
        bigint category_id FK "nullable"
        varchar type "income|expense"
        numeric(19,4) amount
        integer total_count
        integer remaining_count
        date next_due_date
        text description "nullable"
        timestamps
    }

    FINANCIAL_GOALS {
        bigint id PK
        bigint user_id FK
        varchar name
        text description "nullable"
        numeric(19,4) target_amount
        char(3) currency "BRL"
        bigint account_id FK "nullable; escopo"
        date target_date
        timestamps
    }

    RECONCILIATIONS {
        bigint id PK
        uuid public_id UK
        bigint user_id FK
        bigint account_id FK
        date period_start
        date statement_date
        numeric(19,4) declared_balance
        numeric(19,4) detected_balance
        numeric(19,4) delta
        json summary
        varchar status "previewed|reconciled|divergent"
        timestamp confirmed_at "nullable"
        timestamps
    }

    RECONCILIATION_ROWS {
        bigint id PK
        bigint reconciliation_id FK
        integer row_number
        date entry_date
        numeric(19,4) amount "signed"
        varchar description "nullable"
        varchar status "matched|missing|invalid"
        varchar match_rule "exact|window|nullable"
        bigint matched_transaction_id "nullable"
        timestamps
    }

    USERS ||--o{ FINANCIAL_ACCOUNTS : "owns"
    USERS ||--o{ CATEGORIES : "owns"
    USERS ||--o{ TRANSACTIONS : "owns"
    USERS ||--o{ BUDGETS : ""
    USERS ||--o{ TRANSACTION_SCHEDULES : "schedules"
    USERS ||--o{ INSTALLMENTS : "parcels"
    USERS ||--o{ FINANCIAL_GOALS : "saves toward"
    USERS ||--o{ PORTFOLIOS : "owns"
    USERS ||--o{ BROKERS : "owns"
    FINANCIAL_ACCOUNTS ||--o{ TRANSACTIONS : "has"
    FINANCIAL_ACCOUNTS ||--o{ TRANSACTION_SCHEDULES : "debits"
    FINANCIAL_ACCOUNTS ||--o{ INSTALLMENTS : "pays"
    FINANCIAL_ACCOUNTS ||--o{ FINANCIAL_GOALS : "scopes"
    CATEGORIES ||--o{ TRANSACTIONS : "classifies"
    CATEGORIES ||--o{ TRANSACTION_SCHEDULES : "classifies"
    CATEGORIES ||--o{ INSTALLMENTS : "classifies"
    BUDGETS }o--|| CATEGORIES : "targets"
    PORTFOLIOS ||--o{ PORTFOLIO_HOLDINGS : "contains"
    PORTFOLIO_HOLDINGS }o--|| ASSETS : "holds"
    PORTFOLIOS ||--o{ ASSET_TRANSACTIONS : "records"
    ASSET_TRANSACTIONS }o--|| ASSETS : "relates"
    ASSET_TRANSACTIONS }o--o| BROKERS : "executed at"
    ASSETS ||--o{ PRICE_HISTORY : "prices"
    USERS ||--o{ IMPORT_BATCHES : "uploads"
    PORTFOLIOS ||--o{ IMPORT_BATCHES : "targets"
    IMPORT_BATCHES ||--o{ IMPORT_ROWS : "contains"
    USERS ||--o{ RECONCILIATIONS : "reconciles"
    FINANCIAL_ACCOUNTS ||--o{ RECONCILIATIONS : "statements"
    RECONCILIATIONS ||--o{ RECONCILIATION_ROWS : "contains"
    RECONCILIATION_ROWS }o--o| TRANSACTIONS : "matches"
```

## Índices implementados

- `transactions` por `(user_id, transaction_date)`, `(account_id, transaction_date)` e `transfer_id`.
- `asset_transactions` por `(portfolio_id, transaction_date)`.
- `price_history` único por `(asset_id, price_date)`.
- `budgets` por `(user_id, category_id, period)`.
- `assets` único por `(market, symbol)`; ativos são globais e o ownership começa na carteira.
- `portfolio_holdings` único por `(portfolio_id, asset_id)`.
- `brokers` por `(user_id, name)`; a corretora é opcional em cada operação.
- `import_batches` por `(user_id, created_at)`, `(user_id, kind, status)` e hash do arquivo.
- `import_rows` único por `(import_batch_id, row_number)` e indexado por `fingerprint`.
- `transaction_schedules` por `(user_id, next_run_date, is_active)`.
- `installments` por `(user_id, next_due_date)` e com `remaining_count > 0` para processamento.
- `financial_goals` por `(user_id, target_date)` e `(user_id, account_id)`.
- `reconciliations` por `(user_id, status)` e `(user_id, account_id)`.
- `reconciliation_rows` único por `(reconciliation_id, row_number)` e por `(reconciliation_id, status)`.

## Migrações (Fase 1)

As migrations `2026_09_04_000100` a `000300` implementam este modelo, com colunas
decimais usando `numeric(19,4)`/`numeric(20,8)`, FKs com `nullOnDelete`,
`restrictOnDelete` ou `cascadeOnDelete` conforme a entidade e *soft deletes* nos
cadastros arquiváveis. Datas de negócio são obrigatórias e não dependem do timezone
do banco.

Transferências usam um `transfer_id` UUID compartilhado pelas duas transações
(`transfer_out` e `transfer_in`), evitando a dependência circular de uma FK entre elas.

## Migrações (Fase 9)

A migration `2026_09_07_000100_create_schedules_and_installments.php` adiciona:

- **`transaction_schedules`** — recorrências que materializam transações reais
  (diária/semanal/mensal/anual). `next_run_date` guarda a próxima execução; o
  processamento avança a data e, se `ends_on` for ultrapassado, desativa a recorrência.
  Transferências usam `destination_account_id` e proíbem `category_id`.
- **`installments`** — parcelas mensais fixas. `total_count`/`remaining_count`
  controlam o saldo; ao expirar a última parcela a linha é desativada.

Ambas são filhas de `financial_accounts`/`categories` e base para a agenda
(`docs/recurring-agenda.md`) e a projeção de saldo.

## Migrações (Fase 10)

A migration `2026_09_08_000100_create_financial_goals_table.php` adiciona:

- **`financial_goals`** — metas de economia com valor alvo, moeda, prazo e escopo
  opcional por conta. `account_id` nulo significa "todas as contas na moeda da meta";
  o progresso é o saldo corrente do escopo contra `target_amount`
  (ver `docs/financial-goals.md`). Excluir uma conta remove as metas vinculadas
  (`cascadeOnDelete`).

## Migrações (Fase 11)

A migration `2026_09_08_000200_create_reconciliation_tables.php` adiciona:

- **`reconciliations`** — comparação extrato declarado × saldo detectado da conta
  no período (`period_start` → `statement_date`). `declared_balance` vem do extrato;
  `detected_balance` é a soma BCMath dos créditos/débitos do período; `delta` é a
  diferença. O status (`previewed` → `reconciled`/`divergent`) é fixado na
  confirmação: `reconciled` exige o banco certo dentro de 1 centavo e nenhuma
  divergência de linhas (ver `docs/reconciliation.md`).
- **`reconciliation_rows`** — uma linha por registro declarado, com `match_rule`
  (`exact` = data idêntica, `window` = data ±3 dias), valor absoluto igual e sinal
  compatível. `matched_transaction_id` liga a transação consumida (`nullOnDelete`);
  linhas sem correspondência viram `missing`.

A FK para `transactions` usa `nullOnDelete` para preservar o histórico da
conciliação quando um lançamento é removido.
