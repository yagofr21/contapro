# Metas financeiras — Conta Pro

> **Status:** implementado na **Fase 10** de acordo com o roteiro do README.

Metas financeiras transformam objetivos de economia em progresso mensurável:
o usuário define **quanto** deseja guardar, **em qual moeda**, **até quando** e
**em quais contas**, e o sistema projeta o progresso a partir do saldo corrente.

## Modelo

| Campo            | Descrição                                                                      |
|------------------|--------------------------------------------------------------------------------|
| `name`           | Nome da meta (ex.: "Reserva de emergência").                                   |
| `description`    | Contexto livre, opcional.                                                      |
| `target_amount`  | Valor alvo em `numeric(19,4)` — sempre > 0 e em BCMath no backend.             |
| `currency`       | Moeda ISO 4217 (`BRL`/`EUR`/`USD`).                                            |
| `account_id`     | Escopo opcional. Nulo = **todas as contas na moeda da meta**.                  |
| `target_date`    | Prazo final em `date`.                                                         |

A tabela é `financial_goals`, filha de `users` e opcionalmente de
`financial_accounts` (`cascadeOnDelete`). Índices por `(user_id, target_date)` e
`(user_id, account_id)`.

## Regra de progresso

O progresso usa **BCMath** (nunca float) sobre o saldo corrente do escopo:

1. **Escopo vinculado** → saldo da conta escolhida.
2. **Sem vínculo** → soma dos saldos de todas as contas do usuário na moeda da meta.

Derivados exibidos para cada meta:

- `saved_amount` — saldo corrente do escopo (pode ser negativo; o progresso é zerado).
- `progress` — percentual em `string` (ex.: `50.0000`), limitado a `100.0000`.
- `remaining` — `target_amount - max(saved_amount, 0)`, nunca negativo.
- `is_achieved` — `true` quando o progresso atinge 100% (saldo >= alvo).

Ao atingir a meta, a régua verde indica "Meta atingida"; acima do alvo o excedente
é exibido apenas no saldo guardado.

## Rotas

Recurso `goals` (sem `show`), dentro do grupo autenticado:

| Método | Rota                 | Ação                              |
|--------|----------------------|-----------------------------------|
| GET    | `/goals`             | Lista com progresso calculado.    |
| GET    | `/goals/create`      | Formulário de criação.            |
| POST   | `/goals`             | Cria a meta.                      |
| GET    | `/goals/{goal}/edit` | Formulário de edição.             |
| PUT    | `/goals/{goal}`      | Atualiza a meta.                  |
| DELETE | `/goals/{goal}`      | Remove a meta.                    |

Regras de `FinancialGoalRequest`: `name` obrigatório (255), `description` opcional,
`target_amount` decimal `0,4` > 0, `currency` no enum `Currency`,
`account_id` opcional e pertencente ao usuário (não arquivado), `target_date`
no formato `YYYY-MM-DD`.

Isolamento por usuário via `FinancialGoalPolicy` (criar/ver = autenticado;
atualizar/remover = dono) e validação de posse no `exists` da conta.

## Frontend

- **`Goals/Index.vue`** — cards com nome, descrição, escopo ("Conta: X" ou "Todas
  as contas na moeda"), prazo, saldo guardado, valor alvo, faltantes, régua de
  progresso e ações de editar/excluir.
- **`Goals/Create.vue` / `Goals/Edit.vue`** — envolvem
  `Goals/Partials/GoalForm.vue` (nome, descrição, valor em pt-BR com vírgula,
  moeda, conta opcional e prazo).
- **`AuthenticatedLayout`** — item de navegação **Metas** (`goals.*`, ícone alvo).

## Testes

`tests/Feature/Finance/FinancialGoalTest.php` cobre:

- criação com valor pt-BR normalizado;
- progresso da meta vinculada (saldo vs alvo);
- soma de todas as contas na moeda quando sem vínculo;
- teto de 100% com `remaining` zero e `is_achieved`;
- rejeição de conta de outro usuário;
- bloqueio de edição de meta alheia.

## Modelo de dados

`financial_goals` e seus índices no ER em `docs/data-model.md`
(seção "Migrações (Fase 10)").