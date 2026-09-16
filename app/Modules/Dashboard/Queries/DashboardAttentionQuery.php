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
     * @param  list<array<string, mixed>>  $creditCards
     * @return array{
     *     pending_expected_incomes: int,
     *     due_events: int,
     *     budgets_over_limit: int,
     *     unpriced_holdings: int,
     *     items: list<array{key: string, label: string, href: string, tone: string}>
     * }
     */
    public function forUser(User $user, array $investmentSummaries = [], array $creditCards = []): array
    {
        $today = Carbon::today($user->timezone ?? config('app.timezone'));

        $overdueEvents = $user->transactionSchedules()
            ->where('is_active', true)
            ->whereDate('next_run_date', '<', $today)
            ->count()
            + $user->installments()
                ->where('remaining_count', '>', 0)
                ->whereDate('next_due_date', '<', $today)
                ->count();
        $todayEvents = $user->transactionSchedules()
            ->where('is_active', true)
            ->whereDate('next_run_date', $today)
            ->count()
            + $user->installments()
                ->where('remaining_count', '>', 0)
                ->whereDate('next_due_date', $today)
                ->count();
        $nextEventDate = collect([
            $user->transactionSchedules()->where('is_active', true)->whereDate('next_run_date', '>', $today)->min('next_run_date'),
            $user->installments()->where('remaining_count', '>', 0)->whereDate('next_due_date', '>', $today)->min('next_due_date'),
        ])->filter()->sort()->first();

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

        $items = [];
        if ($overdueEvents > 0) {
            $items[] = ['key' => 'overdue-events', 'label' => $overdueEvents.' vencimento'.($overdueEvents > 1 ? 's atrasados' : ' atrasado'), 'href' => route('agenda.index'), 'tone' => 'rose'];
        }
        if ($todayEvents > 0) {
            $items[] = ['key' => 'today-events', 'label' => $todayEvents.' vencimento'.($todayEvents > 1 ? 's para hoje' : ' para hoje'), 'href' => route('agenda.index'), 'tone' => 'amber'];
        }
        if ($overdueEvents === 0 && $todayEvents === 0 && $nextEventDate !== null) {
            $days = $today->diffInDays(Carbon::parse($nextEventDate));
            $items[] = ['key' => 'next-event', 'label' => 'Próximo vencimento em '.$days.' dia'.((int) $days === 1 ? '' : 's'), 'href' => route('agenda.index'), 'tone' => 'brand'];
        }

        foreach ($creditCards as $card) {
            if (($card['status'] ?? null) === 'overdue') {
                $items[] = ['key' => 'card-overdue-'.$card['id'], 'label' => 'Cartão '.$card['name'].' com fatura atrasada', 'href' => route('accounts.show', $card['id']), 'tone' => 'rose'];
            } elseif (($card['status'] ?? null) === 'due_soon') {
                $items[] = ['key' => 'card-due-'.$card['id'], 'label' => 'Cartão '.$card['name'].' próximo do vencimento', 'href' => route('accounts.show', $card['id']), 'tone' => 'amber'];
            }
        }

        $pendingExpectedIncomes = $user->expectedIncomes()->whereNull('received_at')->count();
        if ($pendingExpectedIncomes > 0) {
            $items[] = ['key' => 'expected', 'label' => $pendingExpectedIncomes.' receita'.($pendingExpectedIncomes > 1 ? 's futuras a confirmar' : ' futura a confirmar'), 'href' => route('expected-incomes.index'), 'tone' => 'emerald'];
        }
        if ($budgetsOverLimit > 0) {
            $items[] = ['key' => 'budgets', 'label' => $budgetsOverLimit.' orçamento'.($budgetsOverLimit > 1 ? 's excedidos' : ' excedido'), 'href' => route('budgets.index'), 'tone' => 'amber'];
        }
        if ($unpricedHoldings > 0) {
            $items[] = ['key' => 'prices', 'label' => $unpricedHoldings.' posição'.($unpricedHoldings > 1 ? 'ões sem cotação' : ' sem cotação'), 'href' => route('assets.index'), 'tone' => 'amber'];
        }

        return [
            'pending_expected_incomes' => $pendingExpectedIncomes,
            'due_events' => $overdueEvents + $todayEvents,
            'budgets_over_limit' => $budgetsOverLimit,
            'unpriced_holdings' => $unpricedHoldings,
            'items' => $items,
        ];
    }
}
