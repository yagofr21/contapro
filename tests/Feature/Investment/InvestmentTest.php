<?php

namespace Tests\Feature\Investment;

use App\Models\User;
use App\Modules\Investment\Enums\AssetTransactionType;
use App\Modules\Investment\Models\Asset;
use App\Modules\Investment\Models\AssetTransaction;
use App\Modules\Investment\Models\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InvestmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_their_own_portfolios(): void
    {
        $user = User::factory()->create();
        Portfolio::factory()->for($user)->create(['name' => 'Minha carteira']);
        Portfolio::factory()->create(['name' => 'Carteira de terceiro']);

        $this->actingAs($user)
            ->get(route('portfolios.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portfolios/Index')
                ->has('portfolios', 1)
                ->where('portfolios.0.name', 'Minha carteira'));
    }

    public function test_user_can_manage_a_portfolio(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('portfolios.store'), [
            'name' => 'Longo prazo',
            'currency' => 'BRL',
        ])->assertRedirect();

        $portfolio = $user->portfolios()->sole();

        $this->actingAs($user)->put(route('portfolios.update', $portfolio), [
            'name' => 'Aposentadoria',
            'currency' => 'USD',
        ])->assertRedirect(route('portfolios.show', $portfolio));

        $this->assertDatabaseHas('portfolios', [
            'id' => $portfolio->id,
            'name' => 'Aposentadoria',
            'currency' => 'USD',
        ]);

        $this->actingAs($user)->delete(route('portfolios.destroy', $portfolio))
            ->assertRedirect(route('portfolios.index'));

        $this->assertSoftDeleted($portfolio);
    }

    public function test_asset_catalog_normalizes_symbols_and_enforces_uniqueness_per_market(): void
    {
        $user = User::factory()->create();

        $payload = [
            'symbol' => ' petr4 ',
            'name' => 'Petrobras PN',
            'type' => 'stock',
            'market' => 'B3',
            'currency' => 'BRL',
        ];

        $this->actingAs($user)->post(route('assets.store'), $payload)
            ->assertRedirect(route('assets.index'));

        $this->assertDatabaseHas('assets', ['symbol' => 'PETR4', 'market' => 'B3']);

        $this->actingAs($user)->post(route('assets.store'), $payload)
            ->assertSessionHasErrors('symbol');
    }

    public function test_buys_and_sells_rebuild_quantity_and_weighted_average_cost(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $asset = Asset::factory()->create();

        $this->storeOperation($user, $portfolio, $asset, 'buy', '10', '10', '2', '2026-01-01');
        $this->storeOperation($user, $portfolio, $asset, 'buy', '5', '16', '1', '2026-01-02');
        $this->storeOperation($user, $portfolio, $asset, 'sell', '4', '20', '0', '2026-01-03');

        $this->assertDatabaseHas('portfolio_holdings', [
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'quantity' => 11,
            'average_cost' => 12.2,
        ]);
    }

    public function test_brazilian_decimals_are_accepted_and_asset_can_be_preselected(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $asset = Asset::factory()->create();

        $this->actingAs($user)->post(route('investment-transactions.store', $portfolio), [
            'asset_id' => $asset->id,
            'type' => 'buy',
            'quantity' => '1.250,5',
            'unit_price' => '10,25',
            'fees' => '2,50',
            'transaction_date' => '2026-09-05',
        ])->assertRedirect(route('portfolios.show', $portfolio));

        $this->assertDatabaseHas('asset_transactions', [
            'asset_id' => $asset->id,
            'quantity' => '1250.50000000',
            'unit_price' => '10.25000000',
            'fees' => '2.5000',
        ]);

        $this->actingAs($user)
            ->get(route('investment-transactions.create', [
                'portfolio' => $portfolio,
                'asset' => $asset->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedAssetId', $asset->id)
                ->where('assets.0.available_quantity', '1250.50000000'));
    }

    public function test_trade_requires_a_positive_unit_price(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $asset = Asset::factory()->create();

        $this->actingAs($user)->post(route('investment-transactions.store', $portfolio), [
            'asset_id' => $asset->id,
            'type' => 'buy',
            'quantity' => '10',
            'unit_price' => '0,00',
            'fees' => '0,00',
            'transaction_date' => '2026-09-05',
        ])->assertSessionHasErrors('unit_price');

        $this->assertDatabaseCount('asset_transactions', 0);
    }

    public function test_oversell_rolls_back_the_operation_and_preserves_the_position(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $asset = Asset::factory()->create();

        $this->storeOperation($user, $portfolio, $asset, 'buy', '3', '10', '0', '2026-01-01');

        $this->actingAs($user)->post(route('investment-transactions.store', $portfolio), [
            'asset_id' => $asset->id,
            'type' => AssetTransactionType::Sell->value,
            'quantity' => '4',
            'unit_price' => '12',
            'fees' => '0',
            'transaction_date' => '2026-01-02',
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(1, AssetTransaction::query()->count());
        $this->assertDatabaseHas('portfolio_holdings', [
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'quantity' => 3,
            'average_cost' => 10,
        ]);
    }

    public function test_operation_asset_must_use_the_portfolio_currency(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create(['currency' => 'BRL']);
        $asset = Asset::factory()->create(['currency' => 'USD']);

        $this->actingAs($user)->post(route('investment-transactions.store', $portfolio), [
            'asset_id' => $asset->id,
            'type' => 'buy',
            'quantity' => '1',
            'unit_price' => '10',
            'fees' => '0',
            'transaction_date' => '2026-01-01',
        ])->assertSessionHasErrors('asset_id');

        $this->assertDatabaseCount('asset_transactions', 0);
    }

    public function test_updating_and_deleting_an_operation_rebuilds_the_position(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $asset = Asset::factory()->create();

        $this->storeOperation($user, $portfolio, $asset, 'buy', '10', '10', '0', '2026-01-01');
        $this->storeOperation($user, $portfolio, $asset, 'buy', '10', '20', '0', '2026-01-02');
        $transaction = AssetTransaction::query()->latest('id')->firstOrFail();

        $this->actingAs($user)->put(route('investment-transactions.update', $transaction), [
            'asset_id' => $asset->id,
            'type' => 'buy',
            'quantity' => '5',
            'unit_price' => '30',
            'fees' => '0',
            'transaction_date' => '2026-01-02',
        ])->assertRedirect(route('portfolios.show', $portfolio));

        $this->assertDatabaseHas('portfolio_holdings', [
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'quantity' => 15,
            'average_cost' => 16.66666666,
        ]);

        $this->actingAs($user)->delete(route('investment-transactions.destroy', $transaction))
            ->assertRedirect(route('portfolios.show', $portfolio));

        $this->assertDatabaseHas('portfolio_holdings', [
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'quantity' => 10,
            'average_cost' => 10,
        ]);
    }

    public function test_user_cannot_access_or_add_operations_to_another_users_portfolio(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create();
        $asset = Asset::factory()->create();

        $this->actingAs($user)->get(route('portfolios.show', $portfolio))->assertForbidden();

        $this->actingAs($user)->post(route('investment-transactions.store', $portfolio), [
            'asset_id' => $asset->id,
            'type' => 'buy',
            'quantity' => '1',
            'unit_price' => '10',
            'fees' => '0',
            'transaction_date' => '2026-01-01',
        ])->assertForbidden();

        $this->assertDatabaseCount('asset_transactions', 0);
    }

    public function test_user_can_register_proceeds_without_changing_the_position(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $asset = Asset::factory()->create();

        $this->storeOperation($user, $portfolio, $asset, 'buy', '10', '20', '0', '2026-01-01');

        foreach ([
            ['type' => 'dividend', 'gross' => '125.5000', 'net' => '106.6750', 'date' => '2026-02-01'],
            ['type' => 'interest', 'gross' => '80.0000', 'net' => '80.0000', 'date' => '2026-03-01'],
        ] as $income) {
            $this->actingAs($user)->post(route('investment-transactions.store', $portfolio), [
                'asset_id' => $asset->id,
                'type' => $income['type'],
                'gross_amount' => $income['gross'],
                'net_amount' => $income['net'],
                'transaction_date' => $income['date'],
            ])->assertRedirect(route('portfolios.show', $portfolio));
        }

        $this->assertDatabaseHas('portfolio_holdings', [
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'quantity' => 10,
            'average_cost' => 20,
        ]);
        $this->assertDatabaseHas('asset_transactions', [
            'portfolio_id' => $portfolio->id,
            'type' => 'dividend',
            'quantity' => 0,
            'unit_price' => 0,
            'fees' => 0,
            'gross_amount' => 125.5,
            'net_amount' => 106.675,
        ]);

        $this->actingAs($user)->get(route('portfolios.show', $portfolio))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.net_income', '186.6750')
                ->where('transactions.0.type', 'interest')
                ->where('transactions.0.gross_amount', '80.0000')
                ->where('transactions.0.net_amount', '80.0000')
                ->where('transactions.1.type', 'dividend'));
    }

    public function test_operation_can_change_between_trade_and_income_and_rebuilds_the_position(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $asset = Asset::factory()->create();
        $this->storeOperation($user, $portfolio, $asset, 'buy', '5', '10', '0', '2026-01-01');
        $transaction = AssetTransaction::query()->sole();

        $this->actingAs($user)->put(route('investment-transactions.update', $transaction), [
            'asset_id' => $asset->id,
            'type' => 'dividend',
            'gross_amount' => '50',
            'net_amount' => '42.5',
            'transaction_date' => '2026-01-01',
        ])->assertRedirect(route('portfolios.show', $portfolio));

        $this->assertDatabaseMissing('portfolio_holdings', [
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
        ]);

        $this->actingAs($user)->put(route('investment-transactions.update', $transaction), [
            'asset_id' => $asset->id,
            'type' => 'buy',
            'quantity' => '3',
            'unit_price' => '12',
            'fees' => '0',
            'gross_amount' => '999',
            'net_amount' => '999',
            'transaction_date' => '2026-01-01',
        ])->assertRedirect(route('portfolios.show', $portfolio));

        $this->assertDatabaseHas('portfolio_holdings', [
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'quantity' => 3,
            'average_cost' => 12,
        ]);
        $this->assertDatabaseHas('asset_transactions', [
            'id' => $transaction->id,
            'gross_amount' => null,
            'net_amount' => null,
        ]);
    }

    public function test_proceeds_validate_amounts_and_implemented_types(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $asset = Asset::factory()->create();
        $payload = [
            'asset_id' => $asset->id,
            'type' => 'dividend',
            'gross_amount' => '10',
            'net_amount' => '11',
            'transaction_date' => '2026-01-01',
        ];

        $this->actingAs($user)->post(route('investment-transactions.store', $portfolio), $payload)
            ->assertSessionHasErrors('net_amount');
        $this->actingAs($user)->post(route('investment-transactions.store', $portfolio), [
            ...$payload,
            'type' => 'split',
        ])->assertSessionHasErrors('type');

        $this->assertDatabaseCount('asset_transactions', 0);
    }

    public function test_user_cannot_edit_another_users_proceed(): void
    {
        $user = User::factory()->create();
        $transaction = AssetTransaction::factory()->dividend()->create();

        $this->actingAs($user)
            ->get(route('investment-transactions.edit', $transaction))
            ->assertForbidden();
        $this->actingAs($user)->put(route('investment-transactions.update', $transaction), [
            'asset_id' => $transaction->asset_id,
            'type' => 'interest',
            'gross_amount' => '90',
            'net_amount' => '90',
            'transaction_date' => '2026-01-01',
        ])->assertForbidden();
        $this->actingAs($user)
            ->delete(route('investment-transactions.destroy', $transaction))
            ->assertForbidden();
    }

    private function storeOperation(
        User $user,
        Portfolio $portfolio,
        Asset $asset,
        string $type,
        string $quantity,
        string $unitPrice,
        string $fees,
        string $date,
    ): void {
        $this->actingAs($user)->post(route('investment-transactions.store', $portfolio), [
            'asset_id' => $asset->id,
            'type' => $type,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'fees' => $fees,
            'transaction_date' => $date,
        ])->assertRedirect(route('portfolios.show', $portfolio));
    }
}
