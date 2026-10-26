# Entrega 1 — dashboard e consistência dos saldos

Implementação aprovada em 26/10/2026. Execução neste checkout, sem commit/push/deploy.
Spec: docs/ui-audit/2026-10-26-diagnostico-proposta.md

Objetivo: saldo atual coerente entre telas, composição verificável e previsto com cobertura explícita.
Arquitetura: manter Controllers/Queries, componentes Vue e Modal existente. Sem dependências novas.

## Tarefas
- [x] Regressões: contas atuais no fuso do usuário; isolamento e moeda nos links de lançamentos; referência temporal e composição dos saldos.
- [x] Backend: corte de data em FinancialAccountController; créditos/débitos no AccountSummaryQuery; referência do dashboard; filtro currency em TransactionController.
- [x] UI: Dashboard com saldo, reserva não definida, faturas e ações prioritárias; modal de composição com saldos iniciais/créditos/débitos; receitas/despesas com links filtrados; previsão explicitamente incompleta quanto a cartões. Accounts/Index com referência da posição; Transactions/Index com filtro de moeda persistente.
- [x] Verificar testes, tipos/lint/build, PHPStan, Pint, navegador desktop/mobile e revisão independente do diff.

## Casos de revisão
- Virada do dia UTC × America/Sao_Paulo: corte por dia do usuário.
- Outra moeda e outro usuário: nunca compor total nem aparecer no detalhamento.
- Transferências: afetam saldo, não receitas/despesas.
- Conta arquivada: preservar escopo atual; informar quando compõe dashboard.
- Sem contas/dados: mostrar ausência; reserva desconhecida nunca vira zero.

## Registro de execução
- Decisão: reutilizar checkout e branch codex/dashboard-clarity; manter mudanças do bootstrap.
- Decisão: não mudar regra de arquivamento nem deduplicar fatura sem vínculo de pagamento; UI explica cobertura.
- RED/GREEN e verificações serão registrados após execução.

## Resultado da execução

- Três regressões executadas primeiro: falharam antes da implementação e passaram depois.
- Suíte completa em SQLite em memória: **205 testes, 2.384 assertions**, aprovada.
- PHPStan: **sem erros**. Pint aplicado aos arquivos PHP alterados.
- Tipos, lint (zero warnings) e build aprovados. Quatro avisos iniciais de indentação foram corrigidos; checks repetidos. Build ainda avisa chunk >500 kB.
- Revisão independente do diff: sem achados materiais.
- Browser 1440×1000 e 390×844: dashboard, composição, faturas, filtros por moeda e Contas. Sem overflow de página nas amostras; modal contém foco, Escape fecha e devolve foco; Shift+Tab do primeiro botão retorna ao último link.
- Composição demo: saldo inicial R$2.500 + entradas R$49.180 − saídas R$35.738,80 = R$15.941,20 na conta principal; mais R$8.000 de poupança = R$23.941,20, também exibidos em Contas. Link de receitas abriu R$9.250 com BRL, realizado e datas corretas. EUR mostrou lista vazia sem perder período.
- Composição de faturas: R$1.716,80 vencidos + R$0 fechados + R$599,90 em formação = R$2.316,70; R$2.099,40 futuros separados.
- Ajuste de acessibilidade na conferência: nome explícito no seletor Moeda. Verificado pelo navegador após build.
- Tema claro e escuro conferidos visualmente; preferência restaurada. Capturas em `entrega1/`, incluindo antes/depois nas mesmas dimensões.
- Decisão mantida: status individual das faturas, idempotência, simplificação do formulário e assistente ficam nas entregas 2–4. Não foram declarados corrigidos nesta entrega.
- Nenhuma gravação no banco de produção, commit, push ou deploy. Somente SQLite isolado de demonstração e testes em memória.
- Limites: sem auditoria WCAG integral ou medição de performance. SearchDocs do Boost tentou consultar a documentação, mas o serviço respondeu com falha de conexão; padrões existentes de Link/Modal foram preservados.
