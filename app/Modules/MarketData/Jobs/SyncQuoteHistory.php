<?php

namespace App\Modules\MarketData\Jobs;

use App\Modules\Investment\Models\Asset;
use App\Modules\MarketData\Actions\PersistPriceHistory;
use App\Modules\MarketData\Contracts\MarketDataProvider;
use App\Modules\MarketData\Exceptions\SymbolNotFound;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncQuoteHistory implements ShouldQueue
{
    use Queueable;

    public int $tries = 25;

    public int $timeout = 30;

    public bool $failOnTimeout = true;

    public function __construct(
        public int $assetId,
        public string $range = '1mo',
        public string $interval = '1d',
    ) {}

    public function handle(MarketDataProvider $provider, PersistPriceHistory $persist): void
    {
        $asset = Asset::query()->find($this->assetId);

        if ($asset === null || ! $asset->is_active || ! in_array($asset->market, $provider->supportedMarkets(), true)) {
            return;
        }

        try {
            $persist->handle($asset, $provider->quoteHistory($asset->symbol, $this->range, $this->interval));
        } catch (SymbolNotFound) {
            $asset->update(['is_active' => false]);
        }
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [
            new RateLimited('brapi'),
            (new WithoutOverlapping("market-data:asset:{$this->assetId}"))->releaseAfter(30)->expireAfter(60),
        ];
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300, 900, 900, 900];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours(6);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Falha definitiva ao sincronizar historico de cotacoes.', [
            'asset_id' => $this->assetId,
            'exception' => $exception !== null ? $exception::class : null,
        ]);
    }
}
