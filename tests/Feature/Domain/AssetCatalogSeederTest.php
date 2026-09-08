<?php

namespace Tests\Feature\Domain;

use App\Enums\Currency;
use App\Modules\Investment\Enums\AssetType;
use App\Modules\Investment\Enums\Market;
use App\Modules\Investment\Models\Asset;
use Database\Seeders\AssetCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_seeder_is_idempotent(): void
    {
        $this->seed(AssetCatalogSeeder::class);
        $count = Asset::count();
        $this->assertGreaterThan(80, $count);

        $this->seed(AssetCatalogSeeder::class);

        $this->assertDatabaseCount('assets', $count);
    }

    public function test_catalog_assets_are_auto_refreshable_and_well_formed(): void
    {
        $this->seed(AssetCatalogSeeder::class);

        $assets = Asset::all();
        $this->assertGreaterThan(80, $assets->count());

        foreach ($assets as $asset) {
            $this->assertMatchesRegularExpression('/^[A-Z0-9.\-]+$/', $asset->symbol);
            $this->assertContains($asset->market, Market::cases());
            $this->assertSame(Currency::BRL, $asset->currency);
            $this->assertContains($asset->type, AssetType::cases());
            $this->assertTrue($asset->is_active);
        }
    }

    public function test_catalog_covers_all_supported_types(): void
    {
        $this->seed(AssetCatalogSeeder::class);

        $this->assertNotEmpty(Asset::where('type', AssetType::Stock)->get());
        $this->assertNotEmpty(Asset::where('type', AssetType::Fii)->get());
        $this->assertNotEmpty(Asset::where('type', AssetType::Etf)->get());
        $this->assertNotEmpty(Asset::where('type', AssetType::Crypto)->get());
        $this->assertNotEmpty(Asset::where('market', Market::Crypto)->get());
    }

    public function test_database_seeder_populates_catalog(): void
    {
        $this->seed();

        $this->assertGreaterThan(80, Asset::count());
    }
}
