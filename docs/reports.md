# Relatorios e agregacoes

O dashboard e os relatorios preservam moedas separadas. Valores em BRL, USD e EUR
nunca sao somados sem uma taxa de cambio explicita.

## Dashboard

- Saldos, receitas, despesas e resultado mensal sao agrupados por moeda.
- O periodo mensal inclui somente o primeiro e o ultimo dia do mes corrente no fuso
  do usuario.
- A posicao de investimentos consolida custo, valor atual, resultado de mercado e
  proventos liquidos por moeda.
- Posicoes sem cotacao usam o custo medio como fallback e sao sinalizadas.

## Relatorios

Os filtros `from`, `to` e `currency` controlam todas as agregacoes da pagina:

- fluxo mensal de receitas e despesas;
- despesas por categoria, incluindo `Sem categoria`;
- proventos liquidos no periodo;
- alocacao atual por tipo de ativo;
- resumo da posicao atual na moeda selecionada.

O periodo padrao cobre os ultimos doze meses e a moeda padrao e BRL.

## Exportacao CSV

`GET /reports/transactions.csv` exporta os lancamentos filtrados em UTF-8 com BOM,
separador `;` e valores decimais canonicos. Entradas `transfer_in` sao omitidas para
nao duplicar transferencias, e celulas que poderiam executar formulas em planilhas sao
prefixadas com apostrofo.
