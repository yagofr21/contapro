# Recorrências, parcelas, agenda e projeção de saldo — Conta Pro

> **Status:** implementado na **Fase 9** de acordo com o roteiro do README.

## Visão geral

O módulo financeiro passa a registrar lançamentos previsíveis sem depender do usuário:

- **Recorrências** (`transaction_schedules`): transações que se repetem sozinhas
  (diária, semanal, mensal, anual) até `ends_on` (se definido) ou indefinidamente.
- **Parcelas** (`installments`): despesas/receitas divididas em um número fixo de
  parcelas mensais.
- **Agenda** (`GET /agenda`): prévia dos próximos 60 dias com os eventos esperados,
  agrupados por data, e uma **projeção de saldo** por conta ao fim do horizonte.

O processamento automático roda via **scheduler** (Agendamento do Laravel, em
`routes/console.php`):

| Comando                | Horário    | Efeito |
| ---                    | ---        | --- |
| `recurring:generate`   | diário 02:30 | Materializa recorrências vencidas como transações e avança a próxima execução |
| `installments:process` | diário 02:40 | Materializa parcelas vencidas e decrementa o saldo restante |

Fuso: `America/Sao_Paulo`; ambos usam `onOneServer()` + `withoutOverlapping()`.

## Recorrências

### Regras de negócio

- `type` pode ser `income`, `expense` ou `transfer`. Transferências exigem
  `destination_account_id` e **proíbem** `category_id`.
- `amount` segue o padrão monetário `numeric(19,4)`; entrada normalizada pelo usuário
  em formato pt-BR (`1.250,50`) e convertida para `1250.5000` na persistência.
- `next_run_date` inicia em `starts_on`; a cada execução é avançada conforme a
  frequência. Se o próximo vencimento ultrapassar `ends_on`, a recorrência é
  desativada (`is_active = false`) sem materializar a transação.
- A API CRUD está sob `/recurring` (resource `except('show')`), com `RecurringSchedulePolicy`
  garantindo isolamento por usuário.

### Processamento

`GenerateScheduledTransactions` (Action em `app/Modules/Finance/Actions/`):

1. Seleciona recorrências ativas com `next_run_date <= hoje`.
2. Para cada uma, materializa uma transação real por `CreateTransaction`
   (mesmas regras de transferências de 2 linhas com `transfer_id`).
3. Avança `next_run_date`; desativa se `ends_on` foi ultrapassado.

As operações são **idempotentes**: cada execução lê o `next_run_date` apenas uma vez,
portanto rodar o comando repetidamente no mesmo dia não duplica lançamentos.

## Parcelas

### Regras de negócio

- `type` é `income` ou `expense` (parcelas de transferência não são suportadas).
- `starts_on` define o primeiro vencimento; depois disso cada parcela vence 1 mês após
  a anterior.
- `total_count` fixo; `remaining_count` decrementa a cada materialização.
- CRUD sob `/installments` (resource `except(['show', 'edit', 'update'])` — não há
  edição após a criação porque o histórico pode já ter lançamentos), com
  `InstallmentPolicy`.

### Processamento

`ProcessInstallments` (Action):

1. Seleciona parcelas com `remaining_count > 0` e `next_due_date <= hoje`.
2. Para cada uma, cria a transação com descrição `"{descricao} ({n}/{total})"`,
   decrementa `remaining_count` e avança `next_due_date` em 1 mês.
3. Quando `remaining_count` chega a 0, para de ser elegível (fica inativo).

## Agenda e projeção

`GET /agenda` usa o Query Object `AgendaProjectionQuery`:

- **Eventos**: percorre recorrências ativas (a partir de `next_run_date`) e parcelas
  pendentes (a partir de `next_due_date`) projetando cada ocorrência até o fim do
  horizonte (padrão 60 dias), ordenadas por data.
- **Projeção**: para cada conta, parte do saldo atual (saldo inicial + créditos -
  débitos) e aplica o efeito líquido dos eventos projetados
  (receitas/transferências-in somam; despesas/transferências-out subtraem),
  resultando em `projected_balance`.

Valores sempre em string com 4 casas decimais (`numeric-string` no backend,
convertidos para `string` no Inertia — ver `docs/financial-calculations.md`).

## Frontend

- `Resources/js/Pages/Agenda/Index.vue` — agenda + projeção por conta.
- `Resources/js/Pages/Recurring/{Index,Create,Edit,Partials/RecurringForm}.vue` — CRUD.
- `Resources/js/Pages/Installments/{Index,Create,Partials/InstallmentForm}.vue` — CRUD.
- `AuthenticatedLayout` ganhou os itens Agenda, Recorrências e Parcelas.

## Testes

- `tests/Feature/Finance/RecurringScheduleTest.php`
- `tests/Feature/Finance/InstallmentTest.php`
- `tests/Feature/Finance/GenerateScheduledTransactionsTest.php`
- `tests/Feature/Finance/ProcessInstallmentsTest.php`
- `tests/Feature/Finance/AgendaProjectionTest.php`

Cobrem criação, validação (transfer sem categoria / com categoria rejeitada),
isolamento entre usuários, idempotência, desativação ao fim do período e projeção de
saldo com múltiplas ocorrências no horizonte.

## Modelo de dados

Consulte `docs/data-model.md` (seção Migrações da Fase 9) para as tabelas
`transaction_schedules` e `installments`.