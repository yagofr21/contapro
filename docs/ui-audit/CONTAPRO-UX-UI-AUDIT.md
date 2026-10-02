# ContaPro — auditoria e direção visual

## Brief e estado inicial
Missão: evoluir a experiência existente de finanças pessoais, priorizando clareza, usabilidade, consistência e acessibilidade. Preservar cálculos, autorização, banco, APIs e moedas isoladas. Estado inicial limpo na branch master em 02/10/2026. Stack: Laravel 13/PHP 8.5, Vue 3/TypeScript/Inertia, Tailwind 4, ECharts e Lucide. Ambiente Docker existente iniciado; Boost instalado conforme AGENTS.md (2.7 → 2.10.1). Não há regras em .ai/rules.

Build inicial aprovado. Testes iniciais: 172 passam, 1 falha (DashboardTest, monthlyTrends.0.months.5.income esperado 700.0000, obtido 0.0000). Falha anterior ao redesign: o fixture criava movimentos nos dias 3 e 4 do mês, futuros quando executado em 02/10. Resolvida estabilizando a data do teste em 15/09/2026; nenhuma regra financeira alterada. Suíte final: 173 testes e 2048 assertions aprovados.

## Mapa real
- Visão geral: resumos por moeda, fluxo dos últimos seis meses, cartões, receitas futuras, movimentos, agenda, categorias e investimentos.
- Financeiro: lançamentos (receitas/despesas/transferências; busca, filtros, paginação, CRUD, parcelas em cartão), contas (dinheiro/corrente/poupança/investimento/cartões), detalhes e pagamento de faturas.
- Planejamento: agenda, receitas futuras, recorrências, parcelas, orçamentos e metas.
- Investimentos: carteiras, posições, operações, ativos/cotações/atualização automática e corretoras.
- Análises e dados: relatórios por período/moeda, exportação CSV, importações com prévia e confirmação, conciliações.
- Sistema: bancos, categorias, perfil, senha, exclusão de conta, tema e preferências da navegação.
- Autenticação: login, cadastro, recuperação/redefinição e confirmação de senha.
Receitas e despesas não possuem páginas separadas; cartões/faturas pertencem a Contas. Não criar rotas fictícias.

## Auditoria priorizada (código e capturas antes/depois)
| Prioridade | Problema | Impacto/tela | Solução |
|---|---|---|---|
| Alto | Resumo financeiro dentro de um grande card com sete cards internos; efeitos e cores competem | Dashboard: dificulta leitura dos quatro indicadores principais | Cabeçalho aberto, quatro indicadores reutilizando StatCard, composição patrimonial em faixa secundária |
| Alto | Valores financeiros truncados | Dashboard/relatórios: esconde saldo, sobretudo alto/mobile | Numerais tabulares, quebra segura, grades com largura útil |
| Alto | Colunas fixas ativadas em 768px e linha mobile com ações e valor lado a lado | Lançamentos: descrição perde espaço/overflow | Desktop a partir de lg; linha mobile em grade com valor e ações na segunda faixa |
| Alto | Limite não informado apresentado como zero disponível | Dashboard/Contas/Faturas: falsa impressão de limite esgotado | Exibir “Não informado”; barra somente quando has_limit |
| Alto | Modal não contém foco; menu mobile sem Escape/restauração | Formulários/navegação: teclado sai do contexto | Contenção de Tab, Escape, restauração do foco e scroll |
| Médio | Toast com pointer-events-none e botão fechar | Todas: botão não recebe clique | Região acessível e interativa, limpeza de timer |
| Médio | Excesso de blobs, blur, gradientes, hover que eleva cards | Todas: ruído visual e incoerência | Superfícies opacas, bordas e sombras suaves; preservar azul e Lucide |
| Médio | Categoria donut sem porcentagem; alocação sem lista de valores | Dashboard/relatórios: depende do gráfico/cor | Lista textual de valores e percentuais; tabela acessível para fluxo |
| Médio | Filtros sem nome acessível e busca somente por placeholder | Lançamentos: teclado/leitor de tela | Labels explícitos e aria-label coerente |
| Médio | Estado vazio sem ação contextual | Financeiro/carteiras: usuário precisa procurar o próximo passo | Reutilizar EmptyState com ação existente |
| Médio | Login com cards decorativos, barra fictícia e formulário dentro de cards | Autenticação: carga visual | Painel com ilustração original, formulário limpo e marca existente |
| Baixo | Títulos pequenos e uppercase excessivo | Navegação e indicadores | Hierarquia tipográfica consistente, labels legíveis |

## Direção e alternativas
Escolhida: consolidar componentes atuais e melhorar hierarquia no Vue existente. Uma troca completa de layout/framework aumenta regressão; CSS global isolado reduz trabalho mas não resolve informação e interação. Evolução incremental combina tokens semânticos, layout sóbrio e ajustes específicos. Marca WalletCards e texto Conta Pro preservados. Não há imagens de produto existentes a preservar além do favicon/marca em código.

Não alterar domínio, queries, cálculos, migrations, autenticação ou serviços. Não inventar histórico de patrimônio: os dados disponíveis representam fluxo de receitas/despesas e posição atual. Sem nova integração ou dark mode: manter e consolidar o existente. Ilustração apenas no painel de autenticação; dados financeiros continuam textuais.
