<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Queries\DashboardAttentionQuery;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Queries\AccountSummaryQuery;
use App\Modules\Finance\Queries\BankOptionsQuery;
use App\Modules\Finance\Queries\CreditCardSummaryQuery;
use App\Modules\Finance\Queries\ExpectedIncomeQuery;
use App\Modules\Investment\Queries\PortfolioValuationQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        AccountSummaryQuery $accountSummary,
        PortfolioValuationQuery $portfolioValuation,
        ExpectedIncomeQuery $expectedIncome,
    ): Response {
        $user = $request->user();
        $today = now($user->timezone ?? config('app.timezone'));
        $monthStart = $today->copy()->startOfMonth()->toDateString();
        $monthEnd = $today->copy()->endOfMonth()->toDateString();
        $monthTransactions = $user->transactions()
            ->with(['account:id,currency', 'category:id,name,color'])
            ->whereDate('transaction_date', '>=', $monthStart)
            ->whereDate('transaction_date', '<=', $monthEnd)
            ->whereIn('type', [TransactionType::Income->value, TransactionType::Expense->value])
            ->get();
        $creditCards = (new CreditCardSummaryQuery)->forUser($user);
        $accounts = $accountSummary->forUser($user)
            ->map(function (array $account) use ($creditCards): array {
                if (isset($creditCards[$account['id']])) {
                    $account['credit_card'] = $creditCards[$account['id']];
                }

                return $account;
            });
        $financialSummaries = collect(Currency::cases())->map(function (Currency $currency) use ($accounts, $monthTransactions): array {
            $currencyAccounts = $accounts->where('currency', $currency->value);
            $currencyTransactions = $monthTransactions->filter(
                fn (Transaction $transaction): bool => $transaction->account->currency === $currency,
            );
            $income = $currencyTransactions->where('type', TransactionType::Income)
                ->reduce(fn (string $total, Transaction $transaction): string => bcadd($total, $transaction->amount, 4), '0.0000');
            $expenses = $currencyTransactions->where('type', TransactionType::Expense)
                ->reduce(fn (string $total, Transaction $transaction): string => bcadd($total, $transaction->amount, 4), '0.0000');

            return [
                'currency' => $currency->value,
                'balance' => $currencyAccounts->reduce(
                    fn (string $total, array $account): string => bcadd($total, (string) $account['balance'], 4),
                    '0.0000',
                ),
                'income' => $income,
                'expenses' => $expenses,
                'net' => bcsub($income, $expenses, 4),
            ];
        });

        $trendStart = $today->copy()->startOfMonth()->subMonths(5)->toDateString();
        $trendTransactions = $user->transactions()
            ->with('account:id,currency')
            ->whereIn('type', [TransactionType::Income->value, TransactionType::Expense->value])
            ->whereDate('transaction_date', '>=', $trendStart)
            ->get();
        $monthlyTrends = collect(Currency::cases())->map(function (Currency $currency) use ($trendTransactions, $today): array {
            $months = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = $today->copy()->startOfMonth()->subMonths($i);
                $rows = $trendTransactions->filter(
                    fn (Transaction $transaction): bool => $transaction->account->currency === $currency
                        && $transaction->transaction_date->format('Y-m') === $month->format('Y-m'),
                );
                $months[] = [
                    'month' => $month->format('Y-m'),
                    'income' => $rows->where('type', TransactionType::Income)
                        ->reduce(fn (string $total, Transaction $transaction): string => bcadd($total, $transaction->amount, 4), '0.0000'),
                    'expenses' => $rows->where('type', TransactionType::Expense)
                        ->reduce(fn (string $total, Transaction $transaction): string => bcadd($total, $transaction->amount, 4), '0.0000'),
                ];
            }

            return [
                'currency' => $currency->value,
                'months' => $months,
            ];
        });

        $recent = $user->transactions()
            ->with(['account:id,name,currency', 'category:id,name,color'])
            ->where('type', '!=', TransactionType::TransferIn->value)
            ->latest('transaction_date')
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'description' => $transaction->description ?: 'Sem descricao',
                'type' => $transaction->type->value,
                'amount' => $transaction->amount,
                'date' => $transaction->transaction_date->format('Y-m-d'),
                'account' => $transaction->account->name,
                'currency' => $transaction->account->currency->value,
                'category' => $transaction->category?->name,
                'color' => $transaction->category?->color,
            ]);

        $investments = $portfolioValuation->forUser($user)['summaries'];
        $attention = (new DashboardAttentionQuery)->forUser($user, $investments);

        $categoryExpenses = $monthTransactions
            ->where('type', TransactionType::Expense)
            ->groupBy(fn (Transaction $transaction): string => $transaction->account->currency->value.':'.($transaction->category_id ?? 'none'))
            ->map(function ($transactions): array {
                /** @var Transaction $transaction */
                $transaction = $transactions->first();

                return [
                    'name' => $transaction->category_id !== null ? $transaction->category->name : 'Sem categoria',
                    'color' => $transaction->category_id !== null ? ($transaction->category->color ?? '#94a3b8') : '#94a3b8',
                    'total' => $transactions->reduce(
                        fn (string $total, Transaction $item): string => bcadd($total, $item->amount, 4),
                        '0.0000',
                    ),
                    'currency' => $transaction->account->currency->value,
                ];
            })
            ->values();

        return Inertia::render('Dashboard', [
            'financialSummaries' => $financialSummaries,
            'monthlyTrends' => $monthlyTrends,
            'expectedIncomes' => $expectedIncome->pendingFor($user, 10),
            'investments' => $investments,
            'attention' => $attention,
            'accounts' => $accounts,
            'banks' => (new BankOptionsQuery)->active(),
            'recentTransactions' => $recent,
            'categoryExpenses' => $categoryExpenses,
            'categories' => $user->categories()
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'color'])
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type->value,
                    'color' => $category->color,
                ]),
        ]);
    }
}
