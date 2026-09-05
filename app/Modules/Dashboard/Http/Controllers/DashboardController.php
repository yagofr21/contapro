<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Queries\AccountSummaryQuery;
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
        $accounts = $accountSummary->forUser($user);
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
            'investments' => $portfolioValuation->forUser($user)['summaries'],
            'accounts' => $accounts,
            'recentTransactions' => $recent,
            'categoryExpenses' => $categoryExpenses,
        ]);
    }
}
