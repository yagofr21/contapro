# Evidências de verificação

Execução final em 02/10/2026, no stack Docker existente.

| Check | Resultado |
|---|---|
| php artisan test | 173 passed; 2048 assertions |
| npm run build | aprovado; TypeScript incluído; aviso de chunk ECharts >500KB |
| npm run lint | aprovado; zero warnings |
| Pint no teste PHP alterado | aprovado |
| git diff --check | aprovado |
| Chromium: 18 páginas × 9 larguras | 162 sem overflow de página |
| axe: 6 páginas mobile + dashboard escuro | zero violações nas amostras |
| Tab/Shift+Tab/Escape/foco de retorno | aprovado em modal e menu |
| Alternativa textual de fluxo | dentro do card e abre corretamente |
| HTTP/JS durante navegação | zero erros |
| WebP de autenticação | carregado |

Detalhes legíveis por máquina: after/verification.json e after/results.json. Capturas: pastas before/after. Reprodução: COMPARISON.md. Nenhuma certificação integral de acessibilidade ou medição de Core Web Vitals é inferida destes resultados.

A falha inicial DashboardTest foi isolada: movimentos dos dias 3 e 4 eram futuros quando executados em 02/10. A data fixa com travelTo preserva a regra financeira e estabiliza o fixture. Aplicação PHP/domínio não alterados.
