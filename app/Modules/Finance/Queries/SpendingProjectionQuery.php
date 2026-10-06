<?php

namespace App\Modules\Finance\Queries;

use App\Enums\Currency;
use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use Carbon\CarbonImmutable;

class SpendingProjectionQuery
{
    /** @return list<array{currency: string, months_count: int, period_start: string|null, period_end: string, monthly_income: string|null, monthly_expenses: string|null}> */
    public function forUser(User $user): array
    {
        $end = CarbonImmutable::now($user->timezone ?? config('app.timezone'))->startOfMonth();
        $start = $end->subMonths(3);
        $transactions = $user->transactions()->with('account:id,currency')
            ->whereIn('type', [TransactionType::Income->value, TransactionType::Expense->value])
            ->whereDate('transaction_date', '>=', $start->toDateString())
            ->whereDate('transaction_date', '<', $end->toDateString())->get();
        $accounts = $user->financialAccounts()->get();
        $results = [];
        foreach (Currency::cases() as $currency) {
            $rows = $transactions->filter(fn ($row): bool => $row->account->currency === $currency);
            $firstTransaction = $user->transactions()->whereHas('account', fn ($query) => $query->where('currency', $currency->value))->min('transaction_date');
            $created = $accounts->where('currency', $currency->value)->min('created_at');
            $dates = array_filter([$firstTransaction, $created?->toDateString()]);
            $first = $dates === [] ? $end : CarbonImmutable::parse(min($dates), $end->getTimezone())->startOfMonth()->max($start);
            $months = max(0, min(3, (int) $first->diffInMonths($end, false)));
            $sum = fn (TransactionType $type): string => $rows->where('type', $type)->reduce(fn (string $total, $row): string => bcadd($total, $row->amount, 4), '0.0000');
            $results[] = [
                'currency' => $currency->value,
                'months_count' => $months,
                'period_start' => $months > 0 ? $first->toDateString() : null,
                'period_end' => $end->subDay()->toDateString(),
                'monthly_income' => $months > 0 ? bcdiv($sum(TransactionType::Income), (string) $months, 4) : null,
                'monthly_expenses' => $months > 0 ? bcdiv($sum(TransactionType::Expense), (string) $months, 4) : null,
            ];
        }

        return $results;
    }
}
