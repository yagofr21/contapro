<?php

namespace Tests\Feature\Investment;

use App\Models\User;
use App\Modules\Investment\Enums\Market;
use App\Modules\Investment\Models\Asset;
use App\Modules\Investment\Models\AssetPreference;
use App\Modules\Investment\Models\Portfolio;
use App\Modules\Investment\Models\PortfolioHolding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AssetAutoUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_holding_the_asset_can_toggle_auto_update(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $asset = Asset::factory()->create(['market' => Market::B3, 'is_active' => true]);
        PortfolioHolding::factory()->for($portfolio)->for($asset)->create();

        $this->actingAs($user)->get(route('assets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('assets.0.auto_update', true));

        $this->actingAs($user)->patch(route('assets.auto-update', $asset), ['auto_update' => false])
            ->assertRedirect();

        $this->assertDatabaseHas('asset_preferences', [
            'user_id' => $user->id,
            'asset_id' => $asset->id,
            'auto_update' => false,
        ]);

        $this->actingAs($user)->patch(route('assets.auto-update', $asset), ['auto_update' => true])
            ->assertRedirect();

        $this->assertDatabaseHas('asset_preferences', [
            'user_id' => $user->id,
            'asset_id' => $asset->id,
            'auto_update' => true,
        ]);

        $this->actingAs($user)->get(route('assets.index'))
            ->assertInertia(fn (Assert $page) => $page->where('assets.0.auto_update', true));
    }

    public function test_user_without_a_position_cannot_toggle_auto_update(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $asset = Asset::factory()->create(['market' => Market::B3, 'is_active' => true]);

        $this->actingAs($other)->patch(route('assets.auto-update', $asset), ['auto_update' => false])
            ->assertForbidden();

        $this->assertDatabaseCount('asset_preferences', 0);
    }

    public function test_opted_out_asset_defaults_to_auto_update_for_other_users(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $asset = Asset::factory()->create(['market' => Market::B3, 'is_active' => true]);
        AssetPreference::factory()->create([
            'user_id' => $first->id,
            'asset_id' => $asset->id,
            'auto_update' => false,
        ]);

        $this->actingAs($second)->get(route('assets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('assets.0.auto_update', true));
    }
}
