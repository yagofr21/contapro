<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Investment\Enums\AssetTransactionType;
use Carbon\CarbonImmutable;

class FinancialReportQuery
{
    /**
     * @return array{
     *     summary: array{income: string, expenses: string, net: string, transaction_count: int, net_investment_income: string},
     *     monthly: list<array{month: string, income: string, expenses: string}>,
     *     categories: list<array{name: string, color: string, total: string}>
     * }
     */
    public function forUser(User $user, string $from, string $to, string $currency): array
    {
        $transactions = $user->transactions()
            ->with(['account:id,currency', 'category:id,name,color'])
            ->whereHas('account', fn ($query) => $query->where('currency', $currency))
            ->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to)
            ->where('type', '!=', TransactionType::TransferIn->value)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
        $income = '0.0000';
        $expenses = '0.0000';
        $categories = [];
        $months = [];
        $cursor = CarbonImmutable::parse($from)->startOfMonth();
        $lastMonth = CarbonImmutable::parse($to)->startOfMonth();

        while ($cursor <= $lastMonth) {
            $months[$cursor->format('Y-m')] = ['month' => $cursor->format('Y-m'), 'income' => '0.0000', 'expenses' => '0.0000'];
            $cursor = $cursor->addMonth();
        }

        foreach ($transactions as $transaction) {
            $month = $transaction->transaction_date->format('Y-m');

            if ($transaction->type === TransactionType::Income) {
                $income = bcadd($income, $transaction->amount, 4);
                $months[$month]['income'] = bcadd($months[$month]['income'], $transaction->amount, 4);
            }

            if ($transaction->type === TransactionType::Expense) {
                $expenses = bcadd($expenses, $transaction->amount, 4);
                $months[$month]['expenses'] = bcadd($months[$month]['expenses'], $transaction->amount, 4);
                $name = $transaction->category_id !== null ? $transaction->category->name : 'Sem categoria';
                $categories[$name] ??= [
                    'name' => $name,
                    'color' => $transaction->category_id !== null ? ($transaction->category->color ?? '#94a3b8') : '#94a3b8',
                    'total' => '0.0000',
                ];
                $categories[$name]['total'] = bcadd($categories[$name]['total'], $transaction->amount, 4);
            }
        }

        $netInvestmentIncome = $user->portfolios()
            ->where('currency', $currency)
            ->withSum(['transactions as report_net_income' => fn ($query) => $query
                ->whereIn('type', [AssetTransactionType::Dividend->value, AssetTransactionType::Interest->value])
                ->whereDate('transaction_date', '>=', $from)
                ->whereDate('transaction_date', '<=', $to)], 'net_amount')
            ->get()
            ->reduce(fn (string $total, $portfolio): string => bcadd($total, (string) ($portfolio->getAttribute('report_net_income') ?? 0), 4), '0.0000');

        return [
            'summary' => [
                'income' => $income,
                'expenses' => $expenses,
                'net' => bcsub($income, $expenses, 4),
                'transaction_count' => $transactions->count(),
                'net_investment_income' => $netInvestmentIncome,
            ],
            'monthly' => array_values($months),
            'categories' => array_values($categories),
        ];
    }
}
