<?php

namespace App\Modules\MarketData\Providers;

use App\Modules\Investment\Enums\Market;
use App\Modules\MarketData\Contracts\MarketDataProvider;
use App\Modules\MarketData\Data\MarketQuote;
use App\Modules\MarketData\Exceptions\MarketDataException;
use App\Modules\MarketData\Exceptions\SymbolNotFound;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BrapiProvider implements MarketDataProvider
{
    public function quote(string $symbol): MarketQuote
    {
        $result = $this->result($symbol, '1mo', '1d');

        return new MarketQuote(
            priceDate: CarbonImmutable::parse($this->required($result, 'regularMarketTime'))->toDateString(),
            open: $this->decimal($result['regularMarketOpen'] ?? null),
            high: $this->decimal($result['regularMarketDayHigh'] ?? null),
            low: $this->decimal($result['regularMarketDayLow'] ?? null),
            close: $this->decimal($this->required($result, 'regularMarketPrice')),
            adjustedClose: null,
            volume: isset($result['regularMarketVolume']) ? (int) $result['regularMarketVolume'] : null,
        );
    }

    public function quoteHistory(string $symbol, string $range = '1mo', string $interval = '1d'): array
    {
        $result = $this->result($symbol, $range, $interval);
        $history = $result['historicalDataPrice'] ?? null;

        if (! is_array($history)) {
            throw new MarketDataException('A resposta do provedor nao contem historico de precos.');
        }

        return array_map(function (mixed $row): MarketQuote {
            if (! is_array($row)) {
                throw new MarketDataException('O provedor retornou um registro de preco invalido.');
            }

            return new MarketQuote(
                priceDate: CarbonImmutable::createFromTimestampUTC((int) $this->required($row, 'date'))->toDateString(),
                open: $this->decimal($row['open'] ?? null),
                high: $this->decimal($row['high'] ?? null),
                low: $this->decimal($row['low'] ?? null),
                close: $this->decimal($this->required($row, 'close')),
                adjustedClose: $this->decimal($row['adjustedClose'] ?? null),
                volume: isset($row['volume']) ? (int) $row['volume'] : null,
            );
        }, array_values($history));
    }

    public function supportedMarkets(): array
    {
        return [Market::B3, Market::Crypto];
    }

    /** @return array<string, mixed> */
    private function result(string $symbol, string $range, string $interval): array
    {
        $key = sprintf('market-data:brapi:%s:%s:%s', strtoupper($symbol), $range, $interval);

        return Cache::remember($key, now()->addMinutes(5), function () use ($symbol, $range, $interval): array {
            $response = $this->request()->get('quote/'.rawurlencode($symbol), array_filter([
                'token' => config('services.brapi.token'),
                'range' => $range,
                'interval' => $interval,
                'fundamental' => 'true',
            ], fn (mixed $value): bool => $value !== null && $value !== ''));

            $this->ensureSuccessful($response, $symbol);
            $result = $response->json('results.0');

            if (! is_array($result)) {
                throw new MarketDataException('A resposta do provedor nao contem o ativo solicitado.');
            }

            return $result;
        });
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl((string) config('services.brapi.url'))
            ->acceptJson()
            ->connectTimeout((int) config('services.brapi.connect_timeout'))
            ->timeout((int) config('services.brapi.timeout'));
    }

    private function ensureSuccessful(Response $response, string $symbol): void
    {
        if ($response->status() === 404) {
            throw new SymbolNotFound("Ativo {$symbol} nao encontrado no provedor.");
        }

        if (! $response->successful()) {
            throw new MarketDataException("Falha ao consultar o provedor de mercado (HTTP {$response->status()}).");
        }
    }

    /** @param array<string, mixed> $values */
    private function required(array $values, string $key): mixed
    {
        if (! array_key_exists($key, $values) || $values[$key] === null) {
            throw new MarketDataException("Campo obrigatorio ausente na resposta: {$key}.");
        }

        return $values[$key];
    }

    private function decimal(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_int($value) && ! is_float($value) && ! is_string($value)) {
            throw new MarketDataException('O provedor retornou um preco invalido.');
        }

        return number_format((float) $value, 8, '.', '');
    }
}
