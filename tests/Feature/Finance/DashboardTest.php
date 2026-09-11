<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\ExpectedIncome;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Models\TransactionSchedule;
use App\Modules\Investment\Enums\AssetTransactionType;
use App\Modules\Investment\Models\Asset;
use App\Modules\Investment\Models\AssetTransaction;
use App\Modules\Investment\Models\Portfolio;
use App\Modules\Investment\Models\PortfolioHolding;
use App\Modules\MarketData\Models\PriceHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_calculates_current_users_summary(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create([
            'initial_balance' => '1000.0000',
        ]);
        Transaction::factory()->for($user)->for($account, 'account')->create([
            'type' => TransactionType::Income,
            'amount' => '500.0000',
            'transaction_date' => now(),
        ]);
        Transaction::factory()->for($user)->for($account, 'account')->create([
            'type' => TransactionType::Expense,
            'amount' => '125.0000',
            'transaction_date' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('financialSummaries.0.currency', 'BRL')
                ->where('financialSummaries.0.balance', '1375.0000')
                ->where('financialSummaries.0.income', '500.0000')
                ->where('financialSummaries.0.expenses', '125.0000')
                ->where('financialSummaries.0.net', '375.0000')
                ->has('recentTransactions', 2));
    }

    public function test_dashboard_separates_currencies_and_summarizes_investments(): void
    {
        $user = User::factory()->create();
        $brl = FinancialAccount::factory()->for($user)->create(['currency' => 'BRL', 'initial_balance' => '100.0000']);
        $usd = FinancialAccount::factory()->for($user)->create(['currency' => 'USD', 'initial_balance' => '50.0000']);
        Transaction::factory()->for($user)->for($brl, 'account')->create([
            'type' => TransactionType::Income,
            'amount' => '20.0000',
            'transaction_date' => now(),
        ]);
        Transaction::factory()->for($user)->for($usd, 'account')->create([
            'type' => TransactionType::Income,
            'amount' => '10.0000',
            'transaction_date' => now(),
        ]);
        Transaction::factory()->for($user)->for($brl, 'account')->create([
            'type' => TransactionType::Income,
            'amount' => '999.0000',
            'transaction_date' => now()->addMonth(),
        ]);
        $portfolio = Portfolio::factory()->for($user)->create(['currency' => 'BRL']);
        $asset = Asset::factory()->create(['currency' => 'BRL']);
        PortfolioHolding::factory()->for($portfolio)->for($asset)->create(['quantity' => '2', 'average_cost' => '100']);
        PriceHistory::factory()->for($asset)->create(['close' => '110', 'adjusted_close' => '120', 'price_date' => now()]);
        AssetTransaction::factory()->for($portfolio)->for($asset)->create([
            'type' => AssetTransactionType::Dividend,
            'quantity' => '0',
            'unit_price' => '0',
            'fees' => '0',
            'gross_amount' => '12',
            'net_amount' => '10',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financialSummaries.0.balance', '1119.0000')
                ->where('financialSummaries.0.income', '20.0000')
                ->where('financialSummaries.2.currency', 'USD')
                ->where('financialSummaries.2.balance', '60.0000')
                ->where('financialSummaries.2.income', '10.0000')
                ->where('investments.0.cost', '200.0000')
                ->where('investments.0.current_value', '240.0000')
                ->where('investments.0.market_return', '40.0000')
                ->where('investments.0.realized_profit_loss', '0.0000')
                ->where('investments.0.net_income', '10.0000')
                ->where('investments.0.total_return', '50.0000'));
    }

    public function test_dashboard_provides_monthly_income_vs_expense_trend(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create(['currency' => 'BRL']);
        $thisMonth = now()->format('Y-m');
        $lastMonth = now()->subMonth()->format('Y-m');
        Transaction::factory()->for($user)->for($account, 'account')->create([
            'type' => TransactionType::Income,
            'amount' => '700.0000',
            'transaction_date' => now()->startOfMonth()->addDays(2),
        ]);
        Transaction::factory()->for($user)->for($account, 'account')->create([
            'type' => TransactionType::Expense,
            'amount' => '300.0000',
            'transaction_date' => now()->startOfMonth()->addDays(3),
        ]);
        Transaction::factory()->for($user)->for($account, 'account')->create([
            'type' => TransactionType::Income,
            'amount' => '900.0000',
            'transaction_date' => now()->subMonth()->startOfMonth(),
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('monthlyTrends', 3)
                ->where('monthlyTrends.0.currency', 'BRL')
                ->where('monthlyTrends.0.months.4.month', $lastMonth)
                ->where('monthlyTrends.0.months.4.income', '900.0000')
                ->where('monthlyTrends.0.months.5.month', $thisMonth)
                ->where('monthlyTrends.0.months.5.income', '700.0000')
                ->where('monthlyTrends.0.months.5.expenses', '300.0000'));
    }

    public function test_dashboard_reports_items_needing_attention(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create(['currency' => 'BRL']);
        $budget = Budget::factory()->for($user)->create([
            'limit_amount' => '100.0000',
            'starts_on' => now()->startOfMonth(),
        ]);
        Transaction::factory()->for($user)->for($account, 'account')->create([
            'type' => TransactionType::Expense,
            'amount' => '150.0000',
            'category_id' => $budget->category_id,
            'transaction_date' => now(),
        ]);
        ExpectedIncome::factory()->for($user)->create([
            'currency' => 'BRL',
            'received_at' => null,
        ]);
        TransactionSchedule::factory()->for($user)->for($account, 'account')->create([
            'is_active' => true,
            'next_run_date' => today(),
        ]);
        $portfolio = Portfolio::factory()->for($user)->create(['currency' => 'BRL']);
        $asset = Asset::factory()->create(['currency' => 'BRL']);
        PortfolioHolding::factory()->for($portfolio)->for($asset)->create(['quantity' => '2', 'average_cost' => '100']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('attention.pending_expected_incomes', 1)
                ->where('attention.due_events', 1)
                ->where('attention.budgets_over_limit', 1)
                ->where('attention.unpriced_holdings', 1));
    }
}
