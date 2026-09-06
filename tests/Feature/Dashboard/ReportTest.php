<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Investment\Enums\AssetTransactionType;
use App\Modules\Investment\Models\Asset;
use App\Modules\Investment\Models\AssetTransaction;
use App\Modules\Investment\Models\Portfolio;
use App\Modules\Investment\Models\PortfolioHolding;
use App\Modules\MarketData\Models\PriceHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_filters_cash_flow_proceeds_and_current_allocation(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create(['currency' => 'BRL']);
        $category = Category::factory()->for($user)->create(['name' => 'Moradia', 'color' => '#ea580c']);
        $this->transaction($user, $account, 'income', '1000', '2026-01-01');
        $this->transaction($user, $account, 'expense', '250', '2026-03-15', $category);
        $this->transaction($user, $account, 'expense', '50', '2026-03-31');
        $this->transaction($user, $account, 'transfer_out', '100', '2026-02-10');
        $otherUser = User::factory()->create();
        $otherAccount = FinancialAccount::factory()->for($otherUser)->create(['currency' => 'BRL']);
        $this->transaction($otherUser, $otherAccount, 'income', '9999', '2026-02-01');

        $portfolio = Portfolio::factory()->for($user)->create(['currency' => 'BRL']);
        $asset = Asset::factory()->create(['currency' => 'BRL', 'type' => 'stock']);
        PortfolioHolding::factory()->for($portfolio)->for($asset)->create(['quantity' => '2', 'average_cost' => '100']);
        PriceHistory::factory()->for($asset)->create(['close' => '110', 'adjusted_close' => '120', 'price_date' => '2026-03-31']);
        AssetTransaction::factory()->for($portfolio)->for($asset)->create([
            'type' => AssetTransactionType::Dividend,
            'quantity' => '0',
            'unit_price' => '0',
            'fees' => '0',
            'gross_amount' => '12',
            'net_amount' => '10',
            'transaction_date' => '2026-02-20',
        ]);
        AssetTransaction::factory()->sell()->for($portfolio)->for($asset)->create([
            'realized_cost_basis' => '50',
            'realized_profit_loss' => '25',
            'transaction_date' => '2026-03-20',
        ]);

        $this->actingAs($user)->get(route('reports.index', [
            'from' => '2026-01-01',
            'to' => '2026-03-31',
            'currency' => 'BRL',
        ]))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Index')
            ->where('summary.income', '1000.0000')
            ->where('summary.expenses', '300.0000')
            ->where('summary.net', '700.0000')
            ->where('summary.transaction_count', 4)
            ->where('summary.net_investment_income', '10.0000')
            ->where('summary.realized_profit_loss', '25.0000')
            ->where('summary.net_investment_result', '35.0000')
            ->has('monthly', 3)
            ->where('monthly.1.income', '0.0000')
            ->has('categories', 2)
            ->where('investmentSummary.current_value', '240.0000')
            ->where('allocation.0.type', 'stock')
            ->where('allocation.0.value', '240.0000'));
    }

    public function test_report_rejects_invalid_filters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('reports.index', [
            'from' => '2026-03-01',
            'to' => '2026-02-01',
            'currency' => 'GBP',
        ]))->assertSessionHasErrors(['to', 'currency']);
    }

    private function transaction(
        User $user,
        FinancialAccount $account,
        string $type,
        string $amount,
        string $date,
        ?Category $category = null,
    ): void {
        Transaction::factory()->for($user)->for($account, 'account')->create([
            'category_id' => $category?->id,
            'type' => $type,
            'amount' => $amount,
            'transaction_date' => $date,
        ]);
    }
}
