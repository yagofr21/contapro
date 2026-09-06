<?php

namespace Tests\Feature\Investment;

use App\Models\User;
use App\Modules\Investment\Models\Asset;
use App\Modules\Investment\Models\Broker;
use App\Modules\Investment\Models\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrokerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_their_brokers(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('brokers.store'), [
            'name' => 'XP Investimentos',
        ])->assertRedirect(route('brokers.index'));

        $broker = $user->brokers()->sole();
        $this->actingAs($user)->put(route('brokers.update', $broker), [
            'name' => 'XP',
            'is_active' => false,
        ])->assertRedirect(route('brokers.index'));

        $this->assertDatabaseHas('brokers', [
            'id' => $broker->id,
            'name' => 'XP',
            'is_active' => false,
        ]);
    }

    public function test_user_cannot_manage_or_use_another_users_broker(): void
    {
        $user = User::factory()->create();
        $broker = Broker::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $asset = Asset::factory()->create();

        $this->actingAs($user)->put(route('brokers.update', $broker), [
            'name' => 'Invasao',
        ])->assertForbidden();

        $this->actingAs($user)->post(route('investment-transactions.store', $portfolio), [
            'asset_id' => $asset->id,
            'broker_id' => $broker->id,
            'type' => 'buy',
            'quantity' => '10',
            'unit_price' => '10',
            'fees' => '0',
            'transaction_date' => '2026-01-01',
        ])->assertSessionHasErrors('broker_id');
    }
}
