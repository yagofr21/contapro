<?php

namespace Database\Seeders;

use App\Enums\Currency;
use App\Models\User;
use App\Modules\Finance\Enums\BudgetPeriod;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\FinancialAccountType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Investment\Actions\RebuildPortfolioHolding;
use App\Modules\Investment\Enums\AssetTransactionType;
use App\Modules\Investment\Enums\AssetType;
use App\Modules\Investment\Enums\Market;
use App\Modules\Investment\Models\Asset;
use App\Modules\Investment\Models\AssetTransaction;
use App\Modules\Investment\Models\Broker;
use App\Modules\Investment\Models\Portfolio;
use App\Modules\MarketData\Models\PriceHistory;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(RebuildPortfolioHolding $rebuildHolding): void
    {
        $monthStart = now()->startOfMonth();
        $investmentDate = now()->subMonth()->startOfDay();
        $incomeDate = now()->subWeek()->startOfDay();

        $user = User::query()->updateOrCreate(
            ['email' => 'demo@contapro.local'],
            [
                'name' => 'Usuario Demo',
                'password' => 'password',
                'email_verified_at' => now(),
                'locale' => 'pt_BR',
                'timezone' => 'America/Sao_Paulo',
            ],
        );

        $account = FinancialAccount::query()->updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Conta principal'],
            [
                'type' => FinancialAccountType::Checking,
                'currency' => Currency::BRL,
                'initial_balance' => '2500.0000',
                'is_archived' => false,
            ],
        );

        $income = Category::query()->updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Salario'],
            ['type' => CategoryType::Income, 'color' => '#16a34a'],
        );

        $housing = Category::query()->updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Moradia'],
            ['type' => CategoryType::Expense, 'color' => '#ea580c'],
        );

        Transaction::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'account_id' => $account->id,
                'description' => 'Salario mensal',
                'transaction_date' => $monthStart,
            ],
            [
                'category_id' => $income->id,
                'type' => TransactionType::Income,
                'amount' => '7500.0000',
            ],
        );

        Budget::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'category_id' => $housing->id,
                'period' => BudgetPeriod::Monthly->value,
                'starts_on' => $monthStart,
            ],
            ['limit_amount' => '2500.0000', 'ends_on' => null],
        );

        $asset = Asset::query()->updateOrCreate(
            ['market' => Market::B3->value, 'symbol' => 'PETR4'],
            [
                'name' => 'Petroleo Brasileiro S.A.',
                'type' => AssetType::Stock,
                'currency' => Currency::BRL,
                'is_active' => true,
            ],
        );

        $portfolio = Portfolio::query()->updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Carteira principal'],
            ['currency' => Currency::BRL],
        );
        $broker = Broker::query()->updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Corretora Demo'],
            ['is_active' => true],
        );

        AssetTransaction::query()->updateOrCreate(
            [
                'portfolio_id' => $portfolio->id,
                'asset_id' => $asset->id,
                'transaction_date' => $investmentDate,
            ],
            [
                'type' => AssetTransactionType::Buy,
                'broker_id' => $broker->id,
                'quantity' => '100.00000000',
                'unit_price' => '35.50000000',
                'fees' => '4.9000',
                'note' => 'Posicao demonstrativa',
            ],
        );

        AssetTransaction::query()->updateOrCreate(
            [
                'portfolio_id' => $portfolio->id,
                'asset_id' => $asset->id,
                'type' => AssetTransactionType::Dividend,
                'transaction_date' => $incomeDate,
            ],
            [
                'quantity' => '0',
                'broker_id' => $broker->id,
                'unit_price' => '0',
                'fees' => '0',
                'gross_amount' => '85.0000',
                'net_amount' => '85.0000',
                'note' => 'Dividendo demonstrativo',
            ],
        );
        $rebuildHolding->handle($portfolio->id, $asset->id);

        PriceHistory::query()->updateOrCreate(
            ['asset_id' => $asset->id, 'price_date' => $investmentDate],
            [
                'open' => '37.10000000',
                'high' => '38.25000000',
                'low' => '36.90000000',
                'close' => '38.00000000',
                'adjusted_close' => '38.00000000',
                'volume' => 15000000,
            ],
        );
    }
}
