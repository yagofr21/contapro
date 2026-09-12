<?php

namespace App\Modules\Finance\Queries;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\FinancialAccount;
use Illuminate\Support\Collection;

class AccountSummaryQuery
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function forUser(User $user): Collection
    {
        $rows = $user->financialAccounts()
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
            ->orderBy('is_archived')
            ->orderBy('name')
            ->get()
            ->map(fn (FinancialAccount $account): array => [
                'id' => $account->id,
                'name' => $account->name,
                'bank' => $account->bank,
                'color' => $account->color,
                'type' => $account->type->value,
                'currency' => $account->currency->value,
                'initial_balance' => $account->initial_balance,
                'credit_limit' => $account->credit_limit,
                'credit_closing_day' => $account->credit_closing_day,
                'credit_due_day' => $account->credit_due_day,
                'balance' => bcsub(
                    bcadd((string) $account->initial_balance, (string) ($account->credits ?? 0), 4),
                    (string) ($account->debits ?? 0),
                    4,
                ),
                'is_archived' => $account->is_archived,
            ])
            ->all();

        /** @var Collection<int, array<string, mixed>> */
        return new Collection($rows);
    }
}
