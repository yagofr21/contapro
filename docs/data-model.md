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

    PORTFOLIO_HOLDINGS {
        bigint id PK
        bigint portfolio_id FK
        bigint asset_id FK
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
        numeric(19,4) gross_amount "nullable; proventos"
        numeric(19,4) net_amount "nullable; proventos"
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

    USERS ||--o{ FINANCIAL_ACCOUNTS : "owns"
    USERS ||--o{ CATEGORIES : "owns"
    USERS ||--o{ TRANSACTIONS : "owns"
    USERS ||--o{ BUDGETS : ""
    USERS ||--o{ PORTFOLIOS : "owns"
    FINANCIAL_ACCOUNTS ||--o{ TRANSACTIONS : "has"
    CATEGORIES ||--o{ TRANSACTIONS : "classifies"
    BUDGETS }o--|| CATEGORIES : "targets"
    PORTFOLIOS ||--o{ PORTFOLIO_HOLDINGS : "contains"
    PORTFOLIO_HOLDINGS }o--|| ASSETS : "holds"
    PORTFOLIOS ||--o{ ASSET_TRANSACTIONS : "records"
    ASSET_TRANSACTIONS }o--|| ASSETS : "relates"
    ASSETS ||--o{ PRICE_HISTORY : "prices"
```

## Índices implementados

- `transactions` por `(user_id, transaction_date)`, `(account_id, transaction_date)` e `transfer_id`.
- `asset_transactions` por `(portfolio_id, transaction_date)`.
- `price_history` único por `(asset_id, price_date)`.
- `budgets` por `(user_id, category_id, period)`.
- `assets` único por `(market, symbol)`; ativos são globais e o ownership começa na carteira.
- `portfolio_holdings` único por `(portfolio_id, asset_id)`.

## Migrações (Fase 1)

As migrations `2026_09_04_000100` a `000300` implementam este modelo, com colunas
decimais usando `numeric(19,4)`/`numeric(20,8)`, FKs com `nullOnDelete`,
`restrictOnDelete` ou `cascadeOnDelete` conforme a entidade e *soft deletes* nos
cadastros arquiváveis. Datas de negócio são obrigatórias e não dependem do timezone
do banco.

Transferências usam um `transfer_id` UUID compartilhado pelas duas transações
(`transfer_out` e `transfer_in`), evitando a dependência circular de uma FK entre elas.
