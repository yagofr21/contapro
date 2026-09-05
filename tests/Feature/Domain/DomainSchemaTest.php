<?php

namespace Tests\Feature\Domain;

use App\Enums\Currency;
use App\Models\User;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\FinancialAccountType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Investment\Enums\AssetType;
use App\Modules\Investment\Enums\Market;
use App\Modules\Investment\Models\Asset;
use App\Modules\Investment\Models\Portfolio;
use App\Modules\Investment\Models\PortfolioHolding;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DomainSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_one_tables_and_columns_are_available(): void
    {
        $this->assertTrue(Schema::hasColumns('financial_accounts', [
            'user_id', 'type', 'currency', 'initial_balance', 'deleted_at',
        ]));
        $this->assertTrue(Schema::hasColumns('transactions', [
            'account_id', 'category_id', 'transfer_id', 'amount', 'transaction_date',
        ]));
        $this->assertTrue(Schema::hasColumns('portfolio_holdings', [
            'portfolio_id', 'asset_id', 'quantity', 'average_cost',
        ]));
        $this->assertTrue(Schema::hasColumns('asset_transactions', [
            'portfolio_id', 'asset_id', 'gross_amount', 'net_amount',
        ]));
        $this->assertTrue(Schema::hasColumns('price_history', [
            'asset_id', 'price_date', 'close', 'adjusted_close',
        ]));
    }

    public function test_finance_models_preserve_decimal_scale_and_relationships(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create([
            'type' => FinancialAccountType::Savings,
            'currency' => Currency::BRL,
            'initial_balance' => '1234.5678',
        ]);
        $category = Category::factory()->for($user)->create([
            'type' => CategoryType::Expense,
        ]);
        $transaction = Transaction::factory()
            ->for($user)
            ->for($account, 'account')
            ->for($category)
            ->create([
                'type' => TransactionType::Expense,
                'amount' => '19.9900',
            ]);

        $this->assertSame('1234.5678', $account->initial_balance);
        $this->assertSame('19.9900', $transaction->amount);
        $this->assertTrue($transaction->account->is($account));
        $this->assertTrue($transaction->category->is($category));
        $this->assertTrue($user->transactions->contains($transaction));
    }

    public function test_assets_are_global_and_unique_by_market_and_symbol(): void
    {
        Asset::factory()->create([
            'market' => Market::B3,
            'symbol' => 'PETR4',
            'type' => AssetType::Stock,
        ]);

        $this->expectException(QueryException::class);

        Asset::factory()->create([
            'market' => Market::B3,
            'symbol' => 'PETR4',
            'type' => AssetType::Stock,
        ]);
    }

    public function test_hard_deleting_a_category_nulls_existing_transaction_reference(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create();
        $transaction = Transaction::factory()
            ->for($user)
            ->for($account, 'account')
            ->for($category)
            ->create();

        $category->forceDelete();

        $this->assertNull($transaction->fresh()->category_id);
    }

    public function test_hard_deleting_a_portfolio_removes_its_holdings(): void
    {
        $portfolio = Portfolio::factory()->create();
        $holding = PortfolioHolding::factory()->for($portfolio)->create();

        $portfolio->forceDelete();

        $this->assertDatabaseMissing('portfolio_holdings', ['id' => $holding->id]);
    }
}
