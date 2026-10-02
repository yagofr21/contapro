# ContaPro — plano de implementação

Objetivo: melhorar a experiência existente com dados reais e sem alterar domínio.
Arquitetura: manter Vue/Inertia, Tailwind e ECharts; consolidar componentes existentes antes das telas. Execução na sessão atual, no checkout solicitado. Especificação: CONTAPRO-UX-UI-AUDIT.md e missão anexada.

## Restrições globais
Preservar rotas, formulários e regras financeiras. Sem nova dependência de produto além do Boost obrigatório. Sem commit/push automático. Sem dados inventados. Respeitar tema escuro e redução de movimento.

## Revisão de riscos
Valores altos/negativos; nomes longos; moeda sem registros; cartão sem limite; foco em modal/menu; telas em 360–1920px. Verificar no navegador e nos testes existentes.

## Etapas
- [x] Baseline: capturar login, dashboard, lançamentos, contas/fatura, carteiras/posição, relatórios e páginas auxiliares; executar build/testes.
- [x] Base: app.css (tokens/superfícies), StatCard/Card/PageHeader/EmptyState e Modal (foco), AuthenticatedLayout (menu/feedback/tema). Build/lint e navegação por teclado.
- [x] Dashboard: reutilizar StatCard, faixa patrimonial, ordem fluxo/categorias antes de blocos secundários, cartões com limite explícito, datas de cotação, texto acessível dos gráficos. Build e testes Dashboard/Finance.
- [x] Financeiro: filtros nomeados, grade mobile dos lançamentos, ações de vazios, resumo de contas por moeda e separação visual dos cartões. Detalhes de fatura sem limite fictício. Build e testes Transaction/CreditCard/Account.
- [x] Investimentos/relatórios: valores completos, alocação textual, estados vazios úteis, data de cotação existente, alinhamento. Build e testes Investment/Report.
- [x] Autenticação: gerar/otimizar imagem original, integrar no GuestLayout, manter formulários e rotas; conferir login/cadastro e mobile.
- [x] Revisão: capturas equivalentes depois, larguras 1920/1440/1366/1280/1024/768/430/390/360, modo escuro, modais, teclado, erros HTTP/JS e assets; refinar.
- [x] Documentar tokens, imagens, antes/depois, verificações, arquivos e pendências reais.
