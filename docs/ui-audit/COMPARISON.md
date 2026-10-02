# ContaPro — comparação antes/depois

Capturas da mesma conta demo, sem popular dados ou registrar operações. Desktop 1440 × 900 e mobile 390 × 844; imagens full-page podem ser mais altas que o viewport. Datas e indicadores da conta continuam reais. Os arquivos before são a linha de base; after mostra o redesign.

| Tela | Antes desktop | Depois desktop | Antes mobile | Depois mobile |
|---|---|---|---|---|
| Login | [imagem](before/login-1440.png) | [imagem](after/login-1440.png) | [imagem](before/login-390.png) | [imagem](after/login-390.png) |
| Dashboard | [imagem](before/dashboard-1440.png) | [imagem](after/dashboard-1440.png) | [imagem](before/dashboard-390.png) | [imagem](after/dashboard-390.png) |
| Lançamentos | [imagem](before/transactions-1440.png) | [imagem](after/transactions-1440.png) | [imagem](before/transactions-390.png) | [imagem](after/transactions-390.png) |
| Contas e cartões | [imagem](before/accounts-1440.png) | [imagem](after/accounts-1440.png) | [imagem](before/accounts-390.png) | [imagem](after/accounts-390.png) |
| Fatura | [imagem](before/invoice-1440.png) | [imagem](after/invoice-1440.png) | [imagem](before/invoice-390.png) | [imagem](after/invoice-390.png) |
| Carteiras | [imagem](before/portfolios-1440.png) | [imagem](after/portfolios-1440.png) | [imagem](before/portfolios-390.png) | [imagem](after/portfolios-390.png) |
| Relatórios | [imagem](before/reports-1440.png) | [imagem](after/reports-1440.png) | [imagem](before/reports-390.png) | [imagem](after/reports-390.png) |

Demais páginas têm capturas nas duas pastas. `after/verification.json` registra 18 páginas × 9 larguras, auditoria axe, erros e verificação de teclado. Capturas adicionais incluem 1366/768/360, dashboard escuro e detalhe da carteira.

## O que comparar
Dashboard: menos cards aninhados, quatro respostas financeiras imediatas, composição secundária, fluxo e categorias acima dos detalhes. Lançamentos: mais espaço para descrição, valor completo e ações separadas no mobile. Contas/cartões: filtros explícitos e limite ausente explicado. Faturas: dívida e vencimentos distinguíveis. Relatórios: métricas legíveis, valores/percentuais e alternativa textual aos gráficos. Login: formulário direto e ilustração original.

## Reprodução
Scripts de evidência ficam nesta pasta, sem dependências de produto adicionadas. Neste ambiente foram executados no container node com Playwright/Chromium e axe instalados em `/tmp/contapro-browser`:

```sh
npm install --prefix /tmp/contapro-browser playwright @axe-core/playwright
/tmp/contapro-browser/node_modules/.bin/playwright install --with-deps chromium
APP_HOST_IP=<IP-do-container-app> node docs/ui-audit/capture.mjs after
APP_HOST_IP=<IP-do-container-app> node docs/ui-audit/verify.mjs
```

O container de execução precisa alcançar a rede do app. Fora do container, omitir APP_HOST_IP e usar APP_URL conforme o servidor. PLAYWRIGHT_MODULE e AXE_MODULE permitem indicar os módulos instalados em outro local. Os scripts usam o preenchimento da conta demo existente; não criam conta nem alteram dados financeiros. Não sobrescrever before para validar o resultado atual. Asserções falham com overflow, violação axe nas amostras, erro HTTP/JS ou foco incorreto.
