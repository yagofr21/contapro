# Cálculos financeiros — Conta Pro

Regras de arredondamento, precisão e fórmulas usadas no sistema.

## Precisão e arredondamento

- **Valores monetários** (saldo, transação, orçamento, provento): `numeric(19,4)` no
  banco; exibição com 2 casas (`R$ 1.234,56`), sempre via `Money`.
- **Preços e quantidades de ativos**: `numeric(20,8)`.
- **Nunca usar `float`** para dinheiro. Todo cálculo usa `bcmath` (`bcadd`, `bcsub`,
  `bcmul`, `bcdiv`, `bcsqrt`) com escala explícita.
- Arredondamentos oportunos: monetário → 4 casas no banco (2 na UI); cotações/quantidade
  → 8 casas (preço com até 8 casas na UI quando necessário).

## Money (Value Object)

`App\ValueObjects\Money` recebe `string|int` e `Currency` (ISO 4217):

```php
$total = Money::fromDecimal('101.99', Currency::BRL);
$total->add(Money::fromDecimal('0.01', Currency::BRL));
```

Operações disponíveis: `add`, `subtract`, `multiply`, `divide` (com divisor != 0),
`equals`, `compareTo`, `isZero`, `isNegative`, `format()` (pt_BR), `toDecimal()`.
Toda operação valida moedas iguais.

## Fórmulas principais

### Saldo da conta

`saldo = saldo_inicial + Σ(income) + Σ(transfer_in) − Σ(expense) − Σ(transfer_out)`

### Custo médio de ativos (Fase 3)

Custo médio ponderado por quantidade mantida (json):

```
custo_medio_novo =
  ( qtd_anterior * custo_medio_anterior + qtd_comprada * preco_unitario + taxas_compra )
  / ( qtd_anterior + qtd_comprada )
```

Venda **não altera** o custo médio (apenas reduz a quantidade). Proventos em dinheiro
requerem decisão de reinvestimento declarada na Fase 4.

### Rentabilidade da carteira

- Rentabilidade simples por posição:
  `retorno = (valor_atual − valor_compra) / valor_compra`.
- Rentabilidade de carteira: XIRR (excel-compatible) com fluxo de caixa por operação.
- Comparativo de performance relativa a benchmark (ex.: CDI para renda fixa).

### Proventos e dividendos

Base tributável não é computada automaticamente; o sistema armazena os valores brutos
e líquidos registrados, deixando a liquidação tributária para o usuário.

- Dividendos e juros usam `gross_amount` e `net_amount` com `numeric(19,4)`.
- O valor líquido deve ser menor ou igual ao bruto.
- Proventos não alteram quantidade nem custo médio da posição.
- O resumo da carteira exibe a soma dos valores líquidos separada do resultado de mercado.

## Cálculo mensal de orçamento

`limite_acumulado = limite_mensal * meses_decorridos_no_período`

O dashboard compara gasto real vs. limite acumulado no período selecionado.

## Fuso horário e datas

Padrão `America/Sao_Paulo` (`config('app.timezone')`). Sempre salvar datas de transação
como `date` (sem timezone) — o contexto de fuso é da exibição.
