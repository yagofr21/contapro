# Conciliação avançada — Conta Pro

> **Status:** implementado na **Fase 11** de acordo com o roteiro do README.

A conciliação avançada compara o **extrato declarado pelo banco** com os
**lançamentos detectados** no Conta Pro, linha a linha e por regras. O objetivo é
responder com clareza: "o que o banco diz" vs "o que o sistema registrou" — e
apontar exatamente onde está a divergência.

Diferente da importação (que grava lançamentos), a conciliação é **somente
leitura**: nenhuma transacção é alterada.

## Conceito

O usuário informa a conta, o período do extrato, o **saldo final declarado** e
envia um CSV de extrato no formato abaixo:

```csv
Data;Descricao;Valor
05/09/2026;Mercado;-200,00
2026-09-11;Salario;500,00
```

- `Data` — `DD/MM/AAAA` ou `AAAA-MM-DD` (mesmas regras dos parsers de importação).
- `Descricao` — texto livre, opcional.
- `Valor` — **sinalizado**: negativo = saída (despesa/transferência de saída),
  positivo = entrada (receita/transferência de entrada), pt-BR com vírgula.

Para cada linha declarada, as regras de casamento procuram uma transação
detectada no mesmo período (do usuário + conta):

| Regra | Critério |
|-------|----------|
| `exact` | Valor absoluto igual **e** data idêntica. |
| `window` | Valor absoluto igual e data dentro de **± 3 dias**. |

O sentido do lançamento é preservado: valor negativo só casa com despesa ou
transferência de saída; valor positivo só com receita ou transferência de entrada.
Cada transação é consumida por no máximo uma linha declarada (sem casamentos
duplos). Na regra `window`, a linha mais próxima da data declarada vence.

## Situações por linha

| Situação | Significado |
|----------|-------------|
| `matched` | Encontrou transação detectada (regra `exact` ou `window`). |
| `missing` | Declarada no extrato, mas **sem correspondência** no sistema. |
| `invalid` | Linha ilegível (valor zero, data/valor malformado). |
| `extras` | Transações do período **não declaradas** no extrato (contadas no resumo e listadas na página, até 100). |

O `summary` do registro guarda `{ total, invalid, matched, missing, extras }`.

## Saldo e status

- `detected_balance` — saldo inicial + créditos (receitas e transferências de
  entrada) − débitos (despesas e transferências de saída) até a data do extrato,
  calculado com BCMath.
- `delta` — `declared_balance − detected_balance` (sinalizado).
- Tolerância de 1 centavo (`abs(delta) <= 0.01`).

O status final é decidido na **confirmação** (recalculada atomicamente naquele
momento, como a importação):

| Status | Condição |
|--------|----------|
| `reconciled` | Sem linhas inválidas, sem `missing`, sem `extras` e `delta` dentro da tolerância. |
| `divergent` | Qualquer divergência (linhas não encontradas/não declaradas/inválidas ou diferença de saldo). |
| `previewed` | Prévida gerada, aguardando confirmação. |

## Rotas

Dentro do grupo autenticado, com binding por `public_id` (UUID):

| Método | Rota                           | Ação                                    |
|--------|--------------------------------|-----------------------------------------|
| GET    | `/reconciliations`             | Lista e formulário de nova conciliação. |
| GET    | `/reconciliations/template`    | Baixa o modelo CSV de extrato.          |
| POST   | `/reconciliations`             | Gera a prévia (`throttle:10,1`).        |
| GET    | `/reconciliations/{id}`        | Detalhe com linhas e extras.            |
| POST   | `/reconciliations/{id}/confirm`| Confirma e fixa o status (`throttle:10,1`). |
| DELETE | `/reconciliations/{id}`        | Remove única prévia (não confirmada).   |

Regras de `StoreReconciliationRequest`: `account_id` pertencente ao usuário e não
arquivada; `period_start` e `statement_date` (`after_or_equal`); `declared_balance`
decimal `0,4`; arquivo CSV de até 2 MB/1.000 linhas. Isolamento por usuário via
`ReconciliationPolicy` (criar/ver = autenticado; confirmar/remover = dono).

## Frontend

- **`Reconciliations/Index.vue`** — etapas (Enviar/Conferir/Confirmar), formulário
  (conta, período, saldo declarado, arquivo), modelo de extrato e histórico.
- **`Reconciliations/Show.vue`** — banner com saldo declarado × detectado,
  diferença, cards de resumo, tabela de linhas com a regra usada e a seção de
  lançamentos não declarados (extras).
- **`AuthenticatedLayout`** — item de navegação **Conciliacoes** (`reconciliations.*`).

## Testes

`tests/Feature/ImportExport/ReconciliationTest.php`:

- regras `exact` e `window` com confirmação `reconciled`;
- linha `missing` e confirmação `divergent`;
- lançamentos não declarados listados como `extras`;
- isolamento total entre usuários (ver/confirmar proibidos);
- validação de conta alheia e cabeçalhos CSV inválidos.

## Modelo de dados

- **`reconciliations`** — cabeçalho da conciliação: conta, `period_start`,
  `statement_date`, `declared_balance`, `detected_balance`, `delta`, `summary`
  (JSON) e `confirmed_at`. Índices por `(user_id, status)` e `(user_id, account_id)`.
- **`reconciliation_rows`** — linhas declaradas com `entry_date`, `amount`
  sinalizado, `description`, `status`, `match_rule` e `matched_transaction_id`
  (FK `nullOnDelete`). Único por `(reconciliation_id, row_number)`.

Ver o ER em `docs/data-model.md` (seção "Migrações (Fase 11)") e a base de
importação em `docs/imports.md`.