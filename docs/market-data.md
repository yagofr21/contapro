# Dados de mercado — Conta Pro

Estratégia de obtenção de cotações e histórico de preços (Fase 4).

> Implementação operacional: adaptador brapi.dev, cache de cinco minutos, persistência
> idempotente, jobs com retry/rate limit, atualização manual autorizada e agenda diária.

## Fonte: brapi.dev

Usamos o **brapi.dev** como provedor primário (adaptador `MarketDataProvider`),
cobrindo ativos B3 (ações, FIIs, ETFs), dólar/ouro e cripto.

- Endpoint principal:
  `GET https://brapi.dev/api/quote/{symbols}?token={TOKEN}&range=1mo&interval=1d&fundamental=true`
- **Token:** variável de ambiente `BRAPI_TOKEN`. Sem token, a API permite um número
  menor de requisições/minuto (rate limit) e responde `HTTP 401` quando o limite é
  excedido. Tratar `HTTP 401/403/429` como rate limit e agendar retry com backoff
  exponencial. Com token válido, configure `BRAPI_REQUESTS_PER_MINUTE` (default 10)
  no `.env` para o backfill e as sincronizações diárias caberem em minutos.
- Histórico: `range` suporta `1d` (intraday), `5d`, `1mo`, `6mo`, `1y`, `5y`, `max`.

## Padrão de integração

```
Interface MarketDataProvider
  ├── quote(string $symbol): MarketQuote            // última cotação
  ├── quoteHistory(string $symbol, ...): MarketQuote[]
  └── supportedMarkets(): array

BrapiProvider (brapi.dev)
```

- **Contrato mínimo:** `quote` e `quoteHistory` (OHLCV + `adjustedClose`).
- **Cache:** cotações do dia com TTL curto (ex.: 5 min) em Redis; histórico diário em
  `price_history` (append de OHLCV por ativo/data).
- **Fallback:** nenhum provedor alternativo na Fase 4; a fila de atualização tenta
  novamente/retenta e sinaliza falha para alerta ao usuário.

## Pipeline de atualização

1. Job `SyncQuote` por símbolo (filas `low|default` em Redis).
2. Job valida símbolo, busca via `BrapiProvider`, grava/atualiza `quote`.
3. Se `history` requerida e ausente, agenda `SyncQuoteHistory` (particionado por range).
4. Rate limit respeitado; falhas entram em retry (Laravel jobs) e são logadas.

A sincronização automática roda às **07h, 12h e 18h** em `America/Sao_Paulo` pelo
comando `market-data:sync`. No ambiente Docker, os serviços `queue` e `scheduler`
mantêm esses processos ativos. Cada ativo usa uma única requisição (`/quote` com
`range=1mo&interval=1d`), que alimenta tanto a cotação quanto o histórico — o
`SyncQuoteHistory` reaproveita o cache de 5 minutos da mesma resposta.

## Tratamento de erros

| Erro | Interpretação | Ação |
| --- | --- | --- |
| 404 | Símbolo não encontrado | Desativa ativo + notifica |
| 429 / 403 / 401 | Rate limit / sem token | Backoff + retry job |
| 5xx | API indisponível | Retry com backoff máximo |
| Timeout | Rede/API lenta | Retry limitado e loga |

## Feriados e mercado fechado

O B3 não abre em feriados; o histórico devolve o último dia útil anterior. Exibição
deve indicar "fechado" quando `price_date` não é o último dia útil esperado. Não
corrigimos feriados automaticamente na Fase 4 (decisão de simplificação).

## Segurança

- `BRAPI_TOKEN` **nunca** no frontend; apenas via servidor (env var).
- Todo acesso externo passa pelo adaptador; nenhum URL de terceiros embutido no front.
