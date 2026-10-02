# ContaPro — sistema visual

## Direção
Clareza financeira primeiro: superfícies opacas, azul já existente para ações, pouca ornamentação e hierarquia consistente. Marca WalletCards/Conta Pro e ícones Lucide preservados. Não representar histórico patrimonial quando só existe fluxo mensal.

## Tokens implementados
Fonte em app.css: Inter com fallback system-ui. Valores usam numerais tabulares e quebra segura (`financial-value`), nunca truncamento nos indicadores centrais.

| Papel | Claro | Escuro |
|---|---|---|
| Fundo | #f5f6f8 | #020617 |
| Superfície | #ffffff | #0f172a |
| Superfície elevada | #ffffff | #1e293b |
| Borda | #e5e7eb | #1e293b |
| Borda de controle | #cbd5e1 | #475569 |
| Texto principal | #1c1917 | #f1f5f9 |
| Texto secundário | #57534e | #cbd5e1 |
| Texto auxiliar | #78716c | #94a3b8 |
| Ação principal | #1b6ef5 | azul existente |
| Sucesso/receita | #047857 | emerald-300 |
| Erro/despesa | #be123c | rose-300 |
| Atenção | #92400e | amber-300 |

Escala de espaçamento: utilitários Tailwind, principalmente 4/8/12/16/24/32px. Controles com raio 10px, cards 16px e sombra `0 2px 8px rgb(15 23 42 / 0.035)`. Título de página 24–30px; indicador 24px; corpo 14px; metadados 12px. Mantidos tamanhos específicos de tabelas quando úteis.

## Componentes e uso
- `StatCard`: label, valor completo, ícone e tom semântico; quatro métricas principais no dashboard.
- `Card`, `PageHeader`, `EmptyState`: títulos, superfícies e ações contextuais consistentes. Reutilizar os slots existentes.
- `CashFlowValues`: alternativa textual por mês ao gráfico de receitas/despesas.
- `CreditCardLimit`: total, utilizado, disponível, percentual e limite excedido. Sem limite informado, explicar a ausência e omitir barra.
- `Modal`: diálogo com foco contido, Escape, restauração e rolagem própria. Também utilizado pelo menu mobile.
- `chartTheme`: paleta reativa ao tema em eixos, legendas, grades e tooltips ECharts.
- `form-control`, `cp-button`, `cp-card`, `cp-link`: base reutilizável; controles principais com 44px.

## Comportamento
Usar texto e ícone junto da cor financeira. Tooltips de gráficos ficam dentro do contêiner. Tabelas densas podem rolar dentro do card; lançamentos trocam para lista abaixo de lg. Grades devem ter `min-w-0` quando abrigam gráficos ou valores longos. Nunca agregar moedas distintas nem transformar ausência de limite/cotação em zero.

Estado de envio mantém disabled/processing do Inertia e o login exibe “Entrando…”. Erros de campo são alertas; toasts têm região status e botão clicável. Foco visível, link de pular navegação, página ativa e expansão do menu anunciados. Tema escuro existente preservado. Movimento reduzido respeitado.

## Manutenção
Usar tokens e componentes existentes antes de adicionar variações. Não introduzir nova família de ícones, framework visual ou efeitos de elevação. Gráficos são dados reais com alternativa textual, sem tendências simuladas. Os formulários auxiliares receberam consolidação de estilos; não foram reescritos como novos fluxos.
