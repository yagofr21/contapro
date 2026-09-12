<?php

namespace Database\Seeders;

use App\Enums\Currency;
use App\Models\User;
use App\Modules\Finance\Enums\BudgetPeriod;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\FinancialAccountType;
use App\Modules\Finance\Enums\ScheduleFrequency;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\ExpectedIncome;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialGoal;
use App\Modules\Finance\Models\Installment;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Models\TransactionSchedule;
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
        $user = $this->user();

        if ($user->transactions()->exists()) {
            return;
        }

        $accounts = $this->accounts($user);
        $categories = $this->categories($user);

        $this->transactions($user, $accounts, $categories);
        $this->expectedIncomes($user, $accounts, $categories);
        $this->budgets($user, $categories);
        $this->installments($user, $accounts, $categories);
        $this->schedules($user, $accounts, $categories);
        $this->goals($user, $accounts);
        $this->investments($user, $rebuildHolding);
    }

    private function user(): User
    {
        return User::query()->updateOrCreate(
            ['email' => 'demo@contapro.local'],
            [
                'name' => 'Usuario Demo',
                'password' => 'password',
                'email_verified_at' => now(),
                'locale' => 'pt_BR',
                'timezone' => 'America/Sao_Paulo',
            ],
        );
    }

    /**
     * @return array<string, FinancialAccount>
     */
    private function accounts(User $user): array
    {
        $rows = [
            ['name' => 'Conta principal', 'type' => FinancialAccountType::Checking, 'currency' => Currency::BRL, 'initial_balance' => '2500.0000', 'bank' => 'nubank', 'color' => '#820AD1'],
            ['name' => 'Poupanca', 'type' => FinancialAccountType::Savings, 'currency' => Currency::BRL, 'initial_balance' => '8000.0000', 'bank' => 'itau', 'color' => '#EC7001'],
            ['name' => 'Cartao de credito', 'type' => FinancialAccountType::CreditCard, 'currency' => Currency::BRL, 'initial_balance' => '0.0000', 'credit_limit' => '8000.0000', 'credit_closing_day' => 15, 'credit_due_day' => 22, 'bank' => 'nubank', 'color' => '#820AD1'],
            ['name' => 'Conta em dolar', 'type' => FinancialAccountType::Checking, 'currency' => Currency::USD, 'initial_balance' => '0.0000', 'bank' => 'inter', 'color' => '#FF7A00'],
        ];

        $accounts = [];
        foreach ($rows as $row) {
            /** @var FinancialAccount $account */
            $account = FinancialAccount::query()->updateOrCreate(
                ['user_id' => $user->id, 'name' => $row['name']],
                [...$row, 'is_archived' => false],
            );
            $accounts[$row['name']] = $account;
        }

        return $accounts;
    }

    /**
     * @return array<string, Category>
     */
    private function categories(User $user): array
    {
        $categories = [];
        foreach (self::categoriesData() as $name => [$type, $color]) {
            /** @var Category $category */
            $category = Category::query()->updateOrCreate(
                ['user_id' => $user->id, 'name' => $name],
                ['type' => $type, 'color' => $color],
            );
            $categories[$name] = $category;
        }

        return $categories;
    }

    /**
     * @param  array<string, FinancialAccount>  $accounts
     * @param  array<string, Category>  $categories
     */
    private function transactions(User $user, array $accounts, array $categories): void
    {
        for ($offset = 5; $offset >= 0; $offset--) {
            $this->income($user, $accounts['Conta principal'], $categories['Salario'], 'Salario mensal', '7500.0000', $this->dateFor($offset, 5));

            $this->expense($user, $accounts['Conta principal'], $categories['Moradia'], 'Aluguel', '2200.0000', $this->dateFor($offset, 3));
            $this->expense($user, $accounts['Conta principal'], $categories['Moradia'], 'Condominio', '650.0000', $this->dateFor($offset, 10));
            $this->expense($user, $accounts['Conta principal'], $categories['Moradia'], 'Energia eletrica', '210.0000', $this->dateFor($offset, 12));
            $this->expense($user, $accounts['Conta principal'], $categories['Moradia'], 'Internet fibra', '129.9000', $this->dateFor($offset, 8));
            $this->expense($user, $accounts['Conta principal'], $categories['Alimentacao'], 'Supermercado', '860.0000', $this->dateFor($offset, 9));
            $this->expense($user, $accounts['Conta principal'], $categories['Alimentacao'], 'Restaurantes', '340.0000', $this->dateFor($offset, 20));
            $this->expense($user, $accounts['Conta principal'], $categories['Alimentacao'], 'Feira', '180.0000', $this->dateFor($offset, 22));
            $this->expense($user, $accounts['Conta principal'], $categories['Transporte'], 'Combustivel', '300.0000', $this->dateFor($offset, 7));
            $this->expense($user, $accounts['Conta principal'], $categories['Transporte'], 'Uber', '110.0000', $this->dateFor($offset, 25));
            $this->expense($user, $accounts['Conta principal'], $categories['Saude'], 'Plano de saude', '480.0000', $this->dateFor($offset, 4));
            $this->expense($user, $accounts['Conta principal'], $categories['Educacao'], 'Curso de ingles', '320.0000', $this->dateFor($offset, 6));
            $this->expense($user, $accounts['Conta principal'], $categories['Lazer'], 'Academia', '119.9000', $this->dateFor($offset, 2));
            $this->expense($user, $accounts['Cartao de credito'], $categories['Compras'], 'Compras online', '250.0000', $this->dateFor($offset, 21));
            $this->expense($user, $accounts['Cartao de credito'], $categories['Assinaturas'], 'Netflix', '55.9000', $this->dateFor($offset, 1));
            $this->expense($user, $accounts['Cartao de credito'], $categories['Assinaturas'], 'Spotify', '21.9000', $this->dateFor($offset, 11));

            if (in_array($offset, [0, 2, 4], true)) {
                $amount = match ($offset) {
                    0 => '1750.0000',
                    2 => '980.0000',
                    default => '1450.0000',
                };
                $this->income($user, $accounts['Conta principal'], $categories['Freelance'], 'Projeto freelancer', $amount, $this->dateFor($offset, 18));
                $this->expense($user, $accounts['Conta principal'], $categories['Lazer'], 'Cinema', '60.0000', $this->dateFor($offset, 14));
            }

            if (in_array($offset, [1, 3], true)) {
                $this->expense($user, $accounts['Conta principal'], $categories['Saude'], 'Remedios', '80.0000', $this->dateFor($offset, 15));
            }
        }
    }

    /**
     * @param  array<string, FinancialAccount>  $accounts
     * @param  array<string, Category>  $categories
     */
    private function expectedIncomes(User $user, array $accounts, array $categories): void
    {
        ExpectedIncome::query()->create([
            'user_id' => $user->id,
            'description' => 'Freelance site institucional',
            'amount' => '2500.0000',
            'currency' => Currency::BRL,
            'account_id' => $accounts['Conta principal']->id,
            'category_id' => $categories['Freelance']->id,
            'expected_date' => $this->relativeDate(7),
        ]);
        ExpectedIncome::query()->create([
            'user_id' => $user->id,
            'description' => 'Bonus semestral',
            'amount' => '1800.0000',
            'currency' => Currency::BRL,
            'account_id' => $accounts['Conta principal']->id,
            'category_id' => $categories['Salario']->id,
            'expected_date' => $this->relativeDate(-3),
        ]);
        ExpectedIncome::query()->create([
            'user_id' => $user->id,
            'description' => 'Restituicao imposto',
            'amount' => '420.0000',
            'currency' => Currency::BRL,
            'account_id' => $accounts['Poupanca']->id,
            'category_id' => $categories['Salario']->id,
            'expected_date' => $this->relativeDate(-20),
            'received_at' => now()->subDays(18),
        ]);
    }

    /**
     * @param  array<string, Category>  $categories
     */
    private function budgets(User $user, array $categories): void
    {
        $limits = [
            'Moradia' => '3600.0000',
            'Alimentacao' => '1200.0000',
            'Lazer' => '450.0000',
            'Assinaturas' => '120.0000',
        ];
        $monthStart = now()->startOfMonth();

        foreach ($limits as $name => $amount) {
            Budget::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'category_id' => $categories[$name]->id,
                    'period' => BudgetPeriod::Monthly->value,
                    'starts_on' => $monthStart,
                ],
                ['limit_amount' => $amount, 'ends_on' => null],
            );
        }
    }

    /**
     * @param  array<string, FinancialAccount>  $accounts
     * @param  array<string, Category>  $categories
     */
    private function installments(User $user, array $accounts, array $categories): void
    {
        Installment::query()->create([
            'user_id' => $user->id,
            'account_id' => $accounts['Cartao de credito']->id,
            'category_id' => $categories['Compras']->id,
            'type' => TransactionType::Expense,
            'amount' => '349.9000',
            'total_amount' => '4198.8000',
            'total_count' => 12,
            'remaining_count' => 7,
            'next_due_date' => now()->toDateString(),
            'description' => 'Notebook Lenovo',
        ]);
    }

    /**
     * @param  array<string, FinancialAccount>  $accounts
     * @param  array<string, Category>  $categories
     */
    private function schedules(User $user, array $accounts, array $categories): void
    {
        $rows = [
            ['description' => 'Salario mensal', 'account' => 'Conta principal', 'category' => 'Salario', 'type' => TransactionType::Income, 'amount' => '7500.0000', 'next' => 5],
            ['description' => 'Internet fibra', 'account' => 'Conta principal', 'category' => 'Moradia', 'type' => TransactionType::Expense, 'amount' => '129.9000', 'next' => 7],
            ['description' => 'Academia Smartfit', 'account' => 'Conta principal', 'category' => 'Lazer', 'type' => TransactionType::Expense, 'amount' => '119.9000', 'next' => 0],
            ['description' => 'Aporte poupanca', 'account' => 'Conta principal', 'category' => null, 'type' => TransactionType::TransferOut, 'amount' => '1000.0000', 'next' => 6, 'destination' => 'Poupanca'],
        ];

        foreach ($rows as $row) {
            $destinationAccountId = isset($row['destination']) ? $accounts[$row['destination']]->id : null;
            $categoryId = $row['category'] !== null ? $categories[$row['category']]->id : null;

            TransactionSchedule::query()->create([
                'user_id' => $user->id,
                'account_id' => $accounts[$row['account']]->id,
                'destination_account_id' => $destinationAccountId,
                'category_id' => $categoryId,
                'type' => $row['type'],
                'amount' => $row['amount'],
                'frequency' => ScheduleFrequency::Monthly,
                'starts_on' => now()->toDateString(),
                'next_run_date' => $this->relativeDate($row['next']),
                'description' => $row['description'],
                'is_active' => true,
            ]);
        }
    }

    /**
     * @param  array<string, FinancialAccount>  $accounts
     */
    private function goals(User $user, array $accounts): void
    {
        $rows = [
            ['name' => 'Reserva de emergencia', 'description' => 'Seis meses de custo de vida.', 'target' => '18000.0000', 'account' => null, 'months' => 18],
            ['name' => 'Viagem para Europa', 'description' => 'Passagens, hospedagem e passeios.', 'target' => '25000.0000', 'account' => 'Conta principal', 'months' => 10],
            ['name' => 'Celular novo', 'description' => 'Troca do aparelho atual.', 'target' => '15000.0000', 'account' => 'Poupanca', 'months' => 4],
        ];

        foreach ($rows as $row) {
            FinancialGoal::query()->create([
                'user_id' => $user->id,
                'name' => $row['name'],
                'description' => $row['description'],
                'target_amount' => $row['target'],
                'currency' => Currency::BRL,
                'account_id' => $row['account'] !== null ? $accounts[$row['account']]->id : null,
                'target_date' => now()->addMonths($row['months'])->toDateString(),
            ]);
        }
    }

    private function investments(User $user, RebuildPortfolioHolding $rebuildHolding): void
    {
        $portfolio = Portfolio::query()->updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Carteira principal'],
            ['currency' => Currency::BRL],
        );
        $broker = Broker::query()->updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Corretora Demo'],
            ['is_active' => true],
        );

        $positions = [
            [
                'asset' => $this->asset(Market::B3, AssetType::Stock, 'PETR4', 'Petroleo Brasileiro S.A.'),
                'buys' => [
                    ['monthsAgo' => 6, 'quantity' => '100.00000000', 'unit_price' => '35.50000000', 'fees' => '4.9000'],
                    ['monthsAgo' => 2, 'quantity' => '50.00000000', 'unit_price' => '37.80000000', 'fees' => '2.9000'],
                ],
                'dividends' => [['daysAgo' => 14, 'amount' => '85.0000']],
                'prices' => ['34.90', '36.20', '35.80', '37.10', '38.40', '40.10'],
            ],
            [
                'asset' => $this->asset(Market::B3, AssetType::Fii, 'BTLG11', 'BTG Pactual Logistica'),
                'buys' => [
                    ['monthsAgo' => 4, 'quantity' => '120.00000000', 'unit_price' => '91.20000000', 'fees' => '5.0000'],
                ],
                'dividends' => [['daysAgo' => 21, 'amount' => '89.5000']],
                'prices' => ['90.00', '91.80', '92.60', '94.20', '95.10', '96.80'],
            ],
            [
                'asset' => $this->asset(Market::B3, AssetType::Etf, 'IVVB11', 'iShares S&P 500'),
                'buys' => [
                    ['monthsAgo' => 5, 'quantity' => '10.00000000', 'unit_price' => '318.50000000', 'fees' => '1.5000'],
                ],
                'dividends' => [],
                'prices' => ['315.00', '322.00', '330.00', '348.00', '362.00', '374.50'],
            ],
            [
                'asset' => $this->asset(Market::Crypto, AssetType::Crypto, 'BTC-BRL', 'Bitcoin'),
                'buys' => [
                    ['monthsAgo' => 3, 'quantity' => '0.00250000', 'unit_price' => '548000.00000000', 'fees' => '25.9000'],
                ],
                'dividends' => [],
                'prices' => ['520000.00', '535000.00', '548000.00', '580000.00', '605000.00', '623000.00'],
            ],
        ];

        foreach ($positions as $position) {
            $this->investmentBuys($portfolio, $broker, $position);
            $this->investmentDividends($portfolio, $broker, $position);
            $this->priceHistory($position);
            $rebuildHolding->handle($portfolio->id, $position['asset']->id);
        }
    }

    /**
     * @param  array<string, mixed>  $position
     */
    private function investmentBuys(Portfolio $portfolio, Broker $broker, array $position): void
    {
        foreach ($position['buys'] as $buy) {
            AssetTransaction::query()->updateOrCreate(
                [
                    'portfolio_id' => $portfolio->id,
                    'asset_id' => $position['asset']->id,
                    'type' => AssetTransactionType::Buy->value,
                    'transaction_date' => $this->dateFor($buy['monthsAgo'], 18),
                ],
                [
                    'broker_id' => $broker->id,
                    'quantity' => $buy['quantity'],
                    'unit_price' => $buy['unit_price'],
                    'fees' => $buy['fees'],
                    'note' => 'Compra demonstrativa',
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $position
     */
    private function investmentDividends(Portfolio $portfolio, Broker $broker, array $position): void
    {
        foreach ($position['dividends'] as $dividend) {
            AssetTransaction::query()->updateOrCreate(
                [
                    'portfolio_id' => $portfolio->id,
                    'asset_id' => $position['asset']->id,
                    'type' => AssetTransactionType::Dividend->value,
                    'transaction_date' => now()->subDays($dividend['daysAgo'])->toDateString(),
                ],
                [
                    'broker_id' => $broker->id,
                    'quantity' => '0.00000000',
                    'unit_price' => '0.00000000',
                    'fees' => '0.0000',
                    'gross_amount' => $dividend['amount'],
                    'net_amount' => $dividend['amount'],
                    'note' => 'Dividendo demonstrativo',
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $position
     */
    private function priceHistory(array $position): void
    {
        for ($offset = 5; $offset >= 0; $offset--) {
            $price = $position['prices'][$offset];
            PriceHistory::query()->updateOrCreate(
                ['asset_id' => $position['asset']->id, 'price_date' => $this->dateFor($offset, 15)],
                [
                    'open' => $price,
                    'high' => bcadd($price, '0.40', 8),
                    'low' => bcsub($price, '0.30', 8),
                    'close' => $price,
                    'adjusted_close' => $price,
                    'volume' => 1500000,
                ],
            );
        }
    }

    private function asset(Market $market, AssetType $type, string $symbol, string $name): Asset
    {
        return Asset::query()->updateOrCreate(
            ['market' => $market->value, 'symbol' => $symbol],
            [
                'name' => $name,
                'type' => $type,
                'currency' => Currency::BRL,
                'is_active' => true,
            ],
        );
    }

    private function income(User $user, FinancialAccount $account, Category $category, string $description, string $amount, string $date): void
    {
        Transaction::query()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => TransactionType::Income,
            'amount' => $amount,
            'transaction_date' => $date,
            'description' => $description,
        ]);
    }

    private function expense(User $user, FinancialAccount $account, Category $category, string $description, string $amount, string $date): void
    {
        Transaction::query()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => TransactionType::Expense,
            'amount' => $amount,
            'transaction_date' => $date,
            'description' => $description,
        ]);
    }

    private function dateFor(int $monthOffset, int $day): string
    {
        $now = now();
        $month = $now->copy()->startOfMonth()->subMonths($monthOffset);
        $lastDay = $monthOffset === 0 ? min($now->day, $month->daysInMonth) : $month->daysInMonth;

        return $month->addDays(min(max($day, 1), $lastDay) - 1)->toDateString();
    }

    private function relativeDate(int $days): string
    {
        return now()->addDays($days)->toDateString();
    }

    /**
     * @return array<string, array{0: CategoryType, 1: string}>
     */
    private static function categoriesData(): array
    {
        return [
            'Salario' => [CategoryType::Income, '#16a34a'],
            'Freelance' => [CategoryType::Income, '#0ea5e9'],
            'Investimentos' => [CategoryType::Income, '#8b5cf6'],
            'Moradia' => [CategoryType::Expense, '#ea580c'],
            'Alimentacao' => [CategoryType::Expense, '#ef4444'],
            'Transporte' => [CategoryType::Expense, '#3b82f6'],
            'Saude' => [CategoryType::Expense, '#14b8a6'],
            'Educacao' => [CategoryType::Expense, '#a855f7'],
            'Lazer' => [CategoryType::Expense, '#ec4899'],
            'Compras' => [CategoryType::Expense, '#eab308'],
            'Assinaturas' => [CategoryType::Expense, '#06b6d4'],
        ];
    }
}
