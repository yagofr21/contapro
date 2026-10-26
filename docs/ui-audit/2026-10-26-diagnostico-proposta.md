# ContaPro — diagnóstico e proposta para revisão

26/10/2026. Proposta aprovada pelo usuário. Entrega 1 implementada; resultado em 2026-10-26-entrega1-plano.md. As observações abaixo registram o diagnóstico anterior à implementação.

## Base da análise

Laravel 13, PHP 8.5, Vue 3/TypeScript, Inertia, Tailwind e ECharts. Módulos Finance, Dashboard, Investment, ImportExport e MarketData com Actions, Queries, Requests e Policies. Checkout inicialmente limpo. PHP 8.5.4 e Composer 2.9.5 disponíveis no WSL. Bootstrap obrigatório atualizou Boost 2.10.2 para 2.10.3; AGENTS.md foi relido e diretrizes geradas em CLAUDE.md também foram lidas. Não há .ai/rules.

Auditoria anterior em docs/ui-audit é contexto histórico, não resultado desta sessão. A aplicação já possui seleção de fatura, pagamento parcial, realizado/previsto, simulação de gastos e aporte manual para metas. Preservar essas entregas.

Navegador: desktop 1440×1000 e mobile 390×844. Instância localhost:8010 com SQLite temporário /tmp/contapro-diagnostico-20261026/database.sqlite e seeders BankSeeder/DemoDataSeeder existentes. Valores observados são demonstração, não finanças do usuário. Banco do .env não utilizado; nenhum pagamento confirmado pela UI.

## Problemas e correções

| Prioridade / estado | Tela e evidência | Impacto | Correção proposta |
|---|---|---|---|
| P1 confirmado | Accounts/Show usa status do cartão na fatura atual. Demo: ciclo até 15/11, vencendo 22/11, mostra Atrasada; histórico do mesmo ciclo mostra Em aberto. Existem ciclos anteriores vencidos. | Usuário entende que fatura futura já venceu. | Estado próprio da fatura selecionada; atraso anterior em alerta separado. |
| P1 confirmado no código | Dashboard chama AccountSummaryQuery com data de hoje; Accounts/Index e Show chamam sem corte; query só filtra data quando recebe throughDate. | Movimento futuro pode mudar saldo de Contas antes do disponível no dashboard. | Unificar saldo atual e exibir futuro como previsão; regressão entre telas. |
| P1 confirmado no código | CreateTransaction grava uma operação por chamada. Request e payCard não exigem chave de idempotência. Processing bloqueia botão, lock do pagamento serializa operações mas não identifica mesma intenção. | Reenvio pode duplicar lançamento ou pagamento parcial quando ainda há saldo. | Chave por intenção e usuário, unicidade persistida, retorno do resultado original no retry e rejeição de payload diferente com mesma chave. |
| P1 confirmado | Disponível soma dinheiro/corrente/poupança. Metas acompanham saldo sem reserva exclusiva, conforme aviso existente. | Somar metas como reservado inventaria uma alocação e poderia duplicar valores. | Saldo em contas separado de compromissos; reservado como não definido enquanto não houver reserva explícita. |
| P1 confirmado no código | AgendaProjectionQuery não cria automaticamente saída de caixa para fatura não agendada. Previsto em 30 dias soma projeções das contas líquidas. | Pode parecer sobra após pagar cartões sem incluir o pagamento. | Explicitar cobertura; deduplicar pagamentos cadastrados e faturas ao calcular sobra, sem descontar compra e pagamento duas vezes. |
| P2 confirmado | Dashboard tem mês e realizado/previsto, mas falta data/hora de cálculo e composição filtrada para disponível/receitas/despesas. | Difícil conferir o total e sua atualização. | Referência do backend no fuso do usuário; links com moeda, período, contas e tipo. |
| P2 confirmado | TransactionForm tem padding externo p-6 e interno px-6. Em 390px: form 358px, campo de valor aproximadamente 261px; tipo ocupa três linhas. | Largura útil reduzida e rolagem excessiva. | Uma camada de espaçamento, valor e conta prioritários, opcionais progressivos e ação acessível ao fim. |
| P2 confirmado no código | TransactionForm usa new Date().toISOString().slice(0,10). | Após 21h no Brasil pode sugerir amanhã. | Data do servidor no fuso do usuário em todos os formulários. |
| P2 confirmado | Fatura já permite escolher ciclo e parcial; histórico mostra saldo restante em amount, sem total/estornos/pagamentos por ciclo lado a lado. | Composição pouco clara. | Detalhe por ciclo com compras, estornos, pagamentos, saldo e estado. |
| P2 confirmado | Metas já estimam data por aporte informado, sem rendimentos. Falta ritmo histórico da conta/meta. | Mantendo o ritmo exige uma estimativa manual de aporte. | Sugerir ritmo com período/cobertura e manter valor editável; sem promessa ou reserva fictícia. |
| P2 hipótese de impacto | Dashboard calcula agenda duas vezes (30/45 dias); resumo de cartão consulta relações por cartão; build gera chunk de 540,43 kB. | Pode pesar com muitos cartões e histórico extenso. Lentidão não medida. | Medir consultas, tempo, volume e transferência antes de otimizar; reutilização de projeções e carregamento em lote se necessário. |

Não confirmado overflow horizontal na amostra mobile. Não realizada nova auditoria integral de contraste/leitor de tela ou medição em produção. Resultados antigos de axe não são atribuídos à sessão atual.

## Direção e alternativas

Recomendo evolução integrada por fluxo, preservando marca azul, componentes, temas e navegação. Tipografia legível, números tabulares, superfícies neutras, espaçamento consistente e toque próximo de 44px. Estados sempre com texto, além de cor. Gráficos vêm após decisões e vencimentos.

Alternativas: ajustes isolados de texto são baratos mas deixam cálculo/reenvio inconsistentes; evolução integrada é recomendada; redesenho amplo com assistente primeiro aumenta risco e adia tarefas essenciais. Direção aprovada pelo usuário em 26/10/2026.

Proposta visual: dashboard com saldo em contas, reservado não definido e composição de compromissos; fatura com estado próprio e atraso anterior separado; lançamento compacto com valor e conta primeiro. Valores demonstrativos e campos ausentes explicitados. Não fabricar saldo livre nem totais de compras/pagamentos indisponíveis.

## Plano em quatro entregas verificáveis

1. Dashboard e hierarquia: DashboardController, Dashboard.vue, AccountSummaryQuery, Accounts/Index, StatCard e agenda. Referência temporal única, composição e links, cobertura dos compromissos e ausência de reserva. Verificar totais entre telas, moedas e movimentos futuros.
2. Faturas e pagamento: CreditCardSummaryQuery, Accounts/Show, CardPaymentForm e payCard. Estado da fatura, composição por ciclo, parcial e validação de excesso preservados. Idempotência de pagamento com mecanismo compartilhável. Regressões de vencimento, fechamento, retry e usuário alheio.
3. Lançamentos e celular: TransactionForm, CreateTransaction, TransactionController/Request e esquema para idempotência. Mesma proteção para receita, despesa, transferência e parcelamento. Data no fuso correto, menos padding, preenchimentos reutilizáveis sem sobrescrever escolhas, erros junto aos campos e confirmação. Verificar teclado e celular.
4. Orientações e assistente: SpendingProjection, FinancialGoalQuery, Goals/Index e queries de leitura. Premissas de sobra e ritmo histórico explícitas. Assistente como subentrega separada: regras locais extraem campos, usam somente opções do usuário autenticado e criam rascunho editável. Conta/categoria/data ambíguas exigem escolha. Mensagem de rendimento de R$18,50 prepara receita; somente confirmação explícita grava pela action validada e idempotente. Respostas usam cálculos reais, período e fonte. Regras locais não são modelo de linguagem; IA externa futura é opcional e fora deste escopo.

Cada entrega inclui carregamento, vazio, erro e sucesso, regressões pertinentes, verificações existentes e capturas equivalentes desktop/mobile. Precisão monetária no backend, moedas isoladas, transferências fora de receitas/despesas, acesso autenticado preservado. Não criar reserva financeira nem mudar contas arquivadas sem decisão de domínio. Sem dependência paga, IA externa, commit, push ou deploy.

## Verificações executadas

- DashboardTest, CreditCardTest, TransactionTest e FinancialGoalTest: 43 testes / 507 assertions aprovados, SQLite em memória.
- Serviço Node existente no compose: type-check, lint e build aprovados. Aviso de chunk >500 kB permanece.
- Browser: dashboard, fatura, modal de pagamento desktop e formulário mobile inspecionados; capturas atuais separadas dos históricos.
- Não executados nesta sessão: suíte completa, PHPStan e auditoria automatizada integral de acessibilidade. Nenhuma mudança funcional implementada antes da aprovação.
- Composer reportou três advisories em duas dependências no bootstrap; impacto não investigado neste diagnóstico de UX.

## Ajustes da revisão técnica

- Deduplicar fatura e pagamento somente com vínculo explícito ao cartão/ciclo; coincidência de valor/data/descrição não basta. Sem vínculo, declarar cobertura incompleta e não calcular sobra após cartões. Definir saldo vencido e parcial pela mesma composição de ciclo.
- Crescimento do saldo de uma conta compartilhada por metas não é aporte exclusivo. Usar aportes identificados; sem eles, rotular como variação histórica da conta e manter simulação manual da meta.
- Atualizar a data local ao abrir/reabrir o formulário, inclusive após meia-noite.
- Persistir chave e operação financeira atomicamente, com unicidade e concorrência protegidas. Retry mantém a chave; nova operação intencional recebe chave nova, mesmo com valores iguais.
- Rendimento conversacional pode ser recebimento ou valorização não realizada. O rascunho deve pedir confirmação da natureza antes de propor receita efetiva.
