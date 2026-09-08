<?php

namespace App\Modules\Finance\Queries;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\FinancialAccount;

class FinancialGoalQuery
{
    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     description: string|null,
     *     target_amount: string,
     *     currency: string,
     *     account_id: int|null,
     *     account_name: string|null,
     *     target_date: string,
     *     saved_amount: string,
     *     progress: string,
     *     remaining: string,
     *     is_achieved: bool
     * }>
     */
    public function forUser(User $user): array
    {
        $accounts = $user->financialAccounts()
            ->withSum([
                'transactions as credits' => fn ($query) => $query->whereIn('type', [
                    TransactionType::Income->value,
                    TransactionType::TransferIn->value,
                ]),
            ], 'amount')
            ->withSum([
                'transactions as debits' => fn ($query) => $query->whereIn('type', [
                    TransactionType::Expense->value,
                    TransactionType::TransferOut->value,
                ]),
            ], 'amount')
            ->get()
            ->keyBy('id');

        $rows = [];

        foreach ($user->financialGoals()->with('account:id,name')->orderBy('target_date')->orderBy('name')->get() as $goal) {
            $tracked = $goal->account_id !== null
                ? ($account = $accounts->get($goal->account_id)) !== null ? [$account] : []
                : $accounts->where('currency', $goal->currency->value)->all();

            $saved = array_reduce(
                $tracked,
                fn (string $carry, FinancialAccount $account) => bcadd($carry, $this->balance($account), 4),
                '0',
            );

            $target = $goal->target_amount;
            $effective = bccomp($saved, '0', 4) === -1 ? '0' : $saved;

            if (bccomp($effective, $target, 4) >= 0) {
                $progress = '100.0000';
                $remaining = '0.0000';
                $isAchieved = true;
            } else {
                $progress = bcmul(bcdiv($effective, $target, 8), '100', 4);
                $remaining = bcsub($target, $effective, 4);
                $isAchieved = false;
            }

            $rows[] = [
                'id' => $goal->id,
                'name' => $this->stringify($goal->name),
                'description' => $goal->description,
                'target_amount' => $this->stringify($goal->target_amount),
                'currency' => $this->stringify($goal->currency->value),
                'account_id' => $goal->account_id,
                'account_name' => $goal->account->name ?? null,
                'target_date' => $this->stringify($goal->target_date->format('Y-m-d')),
                'saved_amount' => $this->stringify($saved),
                'progress' => $this->stringify($progress),
                'remaining' => $this->stringify($remaining),
                'is_achieved' => $isAchieved,
            ];
        }

        return $rows;
    }

    private function balance(FinancialAccount $account): string
    {
        return bcsub(
            bcadd((string) $account->initial_balance, (string) ($account->credits ?? 0), 4),
            (string) ($account->debits ?? 0),
            4,
        );
    }

    private function stringify(mixed $value): string
    {
        return (string) $value;
    }
}
