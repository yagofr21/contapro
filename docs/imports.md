# Importacoes CSV

O Conta Pro importa lancamentos financeiros e operacoes de investimentos em tres
etapas: envio, conferencia e confirmacao. O arquivo original e processado em memoria
e nao fica armazenado.

## Limites e formatos

- Arquivos `.csv` ou `.txt` com ate 2 MB e 1.000 linhas de dados.
- Encoding UTF-8 ou Windows-1252, com BOM opcional.
- Separador `;`, `,` ou tabulacao, detectado automaticamente.
- Datas em `AAAA-MM-DD` ou `DD/MM/AAAA`.
- Decimais canonicos (`1234.56`) ou brasileiros (`1.234,56`).
- Modelos vazios dos dois formatos podem ser baixados na tela de importacoes.

O formato financeiro usa `Data`, `Tipo`, `Descricao`, `Conta`, `Conta destino`,
`Categoria`, `Valor` e `Moeda`. Tipos aceitos: Receita, Despesa e Transferencia.

O formato de investimentos usa `Data`, `Tipo`, `Ativo`, `Mercado`, `Corretora`,
`Quantidade`, `Preco unitario`, `Taxas`, `Valor bruto`, `Valor liquido`,
`Proporcao origem`, `Proporcao destino` e `Observacao`. Tipos aceitos: Compra,
Venda, Dividendo, Juros e Desdobramento.

## Validacao e confirmacao

Contas, categorias, carteiras e corretoras sempre sao resolvidas dentro do usuario
autenticado. Ativos devem estar ativos e ter a mesma moeda da carteira. Cada linha
recebe estado `valid`, `invalid`, `duplicate` ou `imported`, alem dos erros de
validacao encontrados.

O fingerprint SHA-256 usa os dados normalizados e identifica repeticoes no mesmo
arquivo e em lotes ja confirmados. Duplicidades nunca sao gravadas. Linhas invalidas
so podem ser ignoradas mediante confirmacao explicita. A gravacao ocorre em uma
transacao de banco e reutiliza as actions de dominio; qualquer falha desfaz o lote
inteiro. Operacoes de investimento sao aplicadas em ordem cronologica e acionam o
replay contabil existente.

Os exports financeiros incluem a conta de destino das transferencias. Cada carteira
tambem oferece um export de operacoes no mesmo formato aceito pela importacao.
Campos de texto iniciados por caracteres de formula sao neutralizados nos exports e
restaurados durante um round-trip pelo proprio Conta Pro.
