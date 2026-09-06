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
não alteram posição ou custo médio.

### Venda e resultado realizado (Fase 7)

Os valores da venda são derivados no servidor durante o replay cronológico:

```
valor_bruto_venda = quantidade_vendida * preco_unitario
valor_liquido_venda = valor_bruto_venda - taxas_venda
custo_baixado = quantidade_vendida * custo_medio_antes_da_venda
resultado_realizado = valor_liquido_venda - custo_baixado
```

O valor bruto, líquido, custo baixado e resultado realizado são persistidos com quatro
casas para auditoria. Uma alteração retroativa recalcula todas as vendas posteriores.

### Desdobramentos e grupamentos (Fase 7)

A proporção é armazenada como quantidade antiga (`split_from`) para quantidade nova
(`split_to`):

```
fator = split_to / split_from
nova_quantidade = quantidade_anterior * fator
novo_custo_medio = custo_total_anterior / nova_quantidade
```

O evento exige posição aberta e preserva o custo total. Proporções maiores aumentam a
quantidade (desdobramento); proporções menores a reduzem (grupamento).

### Rentabilidade da carteira

- Rentabilidade simples por posição:
  `retorno = (valor_atual − valor_compra) / valor_compra`.
- Rentabilidade de carteira: XIRR (excel-compatible) com fluxo de caixa por operação.
- Comparativo de performance relativa a benchmark (ex.: CDI para renda fixa).

O resumo monetário implementado separa:

```
resultado_nao_realizado = valor_atual - custo_das_posicoes_abertas
retorno_total = resultado_nao_realizado + resultado_realizado + proventos_liquidos
```

Percentuais ponderados por tempo e aportes continuam reservados para uma implementação
de XIRR; o sistema não usa o custo atual como denominador enganoso.

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
