<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\User;
use App\Modules\Finance\Enums\BudgetPeriod;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Budget;
use Illuminate\Support\Carbon;

class DashboardAttentionQuery
{
    /**
     * Count the items that require the user's attention today.
     *
     * @param  list<array{currency: string, unpriced_holdings?: int}>  $investmentSummaries
     * @return array{
     *     pending_expected_incomes: int,
     *     due_events: int,
     *     budgets_over_limit: int,
     *     unpriced_holdings: int,
     * }
     */
    public function forUser(User $user, array $investmentSummaries = []): array
    {
        $today = Carbon::today($user->timezone ?? config('app.timezone'));

        $dueEvents = $user->transactionSchedules()
            ->where('is_active', true)
            ->whereDate('next_run_date', '<=', $today)
            ->count()
            + $user->installments()
                ->where('remaining_count', '>', 0)
                ->whereDate('next_due_date', '<=', $today)
                ->count();

        $budgetsOverLimit = $user->budgets()
            ->with('category:id,name,color')
            ->get()
            ->filter(function (Budget $budget) use ($user, $today): bool {
                $endsOn = match ($budget->period) {
                    BudgetPeriod::Monthly => $budget->starts_on->copy()->endOfMonth(),
                    BudgetPeriod::Yearly => $budget->starts_on->copy()->endOfYear(),
                    BudgetPeriod::Custom => $budget->ends_on ?? $budget->starts_on,
                };

                if ($today->toDateString() < $budget->starts_on->toDateString()
                    || $today->toDateString() > $endsOn->toDateString()) {
                    return false;
                }

                $spent = (string) $user->transactions()
                    ->where('type', TransactionType::Expense->value)
                    ->where('category_id', $budget->category_id)
                    ->whereBetween('transaction_date', [$budget->starts_on->toDateString(), $endsOn->toDateString()])
                    ->sum('amount');

                return bccomp($spent, (string) $budget->limit_amount, 4) >= 0;
            })
            ->count();

        $unpricedHoldings = 0;
        foreach ($investmentSummaries as $summary) {
            $unpricedHoldings += $summary['unpriced_holdings'] ?? 0;
        }

        return [
            'pending_expected_incomes' => $user->expectedIncomes()->whereNull('received_at')->count(),
            'due_events' => $dueEvents,
            'budgets_over_limit' => $budgetsOverLimit,
            'unpriced_holdings' => $unpricedHoldings,
        ];
    }
}
