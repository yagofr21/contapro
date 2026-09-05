<?php

namespace Tests\Feature\MarketData;

use App\Models\User;
use App\Modules\Investment\Enums\Market;
use App\Modules\Investment\Models\Asset;
use App\Modules\Investment\Models\Portfolio;
use App\Modules\Investment\Models\PortfolioHolding;
use App\Modules\MarketData\Actions\PersistPriceHistory;
use App\Modules\MarketData\Contracts\MarketDataProvider;
use App\Modules\MarketData\Data\MarketQuote;
use App\Modules\MarketData\Exceptions\SymbolNotFound;
use App\Modules\MarketData\Jobs\SyncQuote;
use App\Modules\MarketData\Jobs\SyncQuoteHistory;
use App\Modules\MarketData\Providers\BrapiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MarketDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_brapi_provider_normalizes_and_caches_quotes_and_history(): void
    {
        Cache::flush();
        config()->set('services.brapi.url', 'https://brapi.test/api');
        config()->set('services.brapi.token', 'secret-token');
        config()->set('services.brapi.timeout', 10);
        config()->set('services.brapi.connect_timeout', 3);
        Http::preventStrayRequests();
        Http::fake(['brapi.test/*' => Http::response($this->brapiPayload())]);
        $provider = new BrapiProvider;

        $quote = $provider->quote('PETR4');
        $history = $provider->quoteHistory('PETR4');

        $this->assertSame('2026-09-04', $quote->priceDate);
        $this->assertSame('47.11000000', $quote->close);
        $this->assertSame('2026-09-04', $history[0]->priceDate);
        $this->assertSame('47.11000000', $history[0]->adjustedClose);
        $this->assertSame(29125700, $history[0]->volume);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://brapi.test/api/quote/PETR4?')
            && $request['token'] === 'secret-token'
            && $request['range'] === '1mo'
            && $request['interval'] === '1d'
            && $request['fundamental'] === 'true');
    }

    public function test_price_history_persistence_is_idempotent_and_updates_existing_days(): void
    {
        $asset = Asset::factory()->create();
        $persist = new PersistPriceHistory;
        $persist->handle($asset, [new MarketQuote(
            '2026-09-04', '47.32000000', '47.45000000', '46.72000000', '47.11000000', '47.11000000', 29125700,
        )]);
        $persist->handle($asset, [new MarketQuote(
            '2026-09-04', '47.32000000', '47.50000000', '46.72000000', '47.20000000', '47.20000000', 30000000,
        )]);

        $this->assertDatabaseCount('price_history', 1);
        $price = $asset->priceHistory()->sole();
        $this->assertSame('47.20000000', $price->close);
        $this->assertSame('47.50000000', $price->high);
        $this->assertSame(30000000, $price->volume);
    }

    public function test_sync_jobs_persist_prices_dispatch_history_and_deactivate_missing_symbols(): void
    {
        Queue::fake();
        $asset = Asset::factory()->create(['market' => Market::B3]);
        $provider = new class implements MarketDataProvider
        {
            public function quote(string $symbol): MarketQuote
            {
                return new MarketQuote('2026-09-04', null, null, null, '47.11000000', null, null);
            }

            public function quoteHistory(string $symbol, string $range = '1mo', string $interval = '1d'): array
            {
                return [];
            }

            public function supportedMarkets(): array
            {
                return [Market::B3];
            }
        };

        (new SyncQuote($asset->id))->handle($provider, new PersistPriceHistory);

        $this->assertDatabaseHas('price_history', ['asset_id' => $asset->id, 'close' => 47.11]);
        Queue::assertPushed(SyncQuoteHistory::class, fn (SyncQuoteHistory $job): bool => $job->assetId === $asset->id);

        $missingProvider = new class implements MarketDataProvider
        {
            public function quote(string $symbol): MarketQuote
            {
                throw new SymbolNotFound;
            }

            public function quoteHistory(string $symbol, string $range = '1mo', string $interval = '1d'): array
            {
                return [];
            }

            public function supportedMarkets(): array
            {
                return [Market::B3];
            }
        };
        (new SyncQuote($asset->id, false))->handle($missingProvider, new PersistPriceHistory);

        $this->assertFalse($asset->fresh()->is_active);
    }

    public function test_only_users_holding_an_asset_can_request_a_refresh(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $asset = Asset::factory()->create();
        PortfolioHolding::factory()->for($portfolio)->for($asset)->create();

        $this->actingAs($user)->get(route('assets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('assets.0.can_refresh', true));
        $this->actingAs($user)->post(route('assets.refresh', $asset))->assertRedirect();
        Queue::assertPushed(SyncQuote::class, fn (SyncQuote $job): bool => $job->assetId === $asset->id);

        $this->actingAs($otherUser)->post(route('assets.refresh', $asset))->assertForbidden();
    }

    public function test_market_data_sync_command_only_dispatches_supported_active_assets(): void
    {
        Queue::fake();
        $supported = Asset::factory()->create(['market' => Market::B3, 'is_active' => true]);
        Asset::factory()->create(['market' => Market::Nasdaq, 'is_active' => true]);
        Asset::factory()->create(['market' => Market::Crypto, 'is_active' => false]);

        $this->artisan('market-data:sync')->assertSuccessful();

        Queue::assertPushed(SyncQuote::class, 1);
        Queue::assertPushed(SyncQuote::class, fn (SyncQuote $job): bool => $job->assetId === $supported->id);
    }

    /** @return array<string, mixed> */
    private function brapiPayload(): array
    {
        return ['results' => [[
            'symbol' => 'PETR4',
            'regularMarketPrice' => 47.11,
            'regularMarketDayHigh' => 47.45,
            'regularMarketDayLow' => 46.72,
            'regularMarketTime' => '2026-09-04T22:27:46.000Z',
            'regularMarketVolume' => 29125700,
            'regularMarketOpen' => 47.32,
            'historicalDataPrice' => [[
                'date' => 1788490800,
                'open' => 47.32,
                'high' => 47.45,
                'low' => 46.72,
                'close' => 47.11,
                'volume' => 29125700,
                'adjustedClose' => 47.11,
            ]],
        ]]];
    }
}
