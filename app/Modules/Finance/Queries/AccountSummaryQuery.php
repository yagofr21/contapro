<?php

namespace App\Modules\Finance\Queries;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\FinancialAccount;
use Illuminate\Support\Collection;

class AccountSummaryQuery
{
    /**
     * @return Collection<int, array{
     *     id: int,
     *     name: string,
     *     type: 'cash'|'checking'|'credit_card'|'investment'|'savings',
     *     currency: 'BRL'|'EUR'|'USD',
     *     initial_balance: numeric-string,
     *     balance: numeric-string,
     *     is_archived: bool
     * }>
     */
    public function forUser(User $user): Collection
    {
        return $user->financialAccounts()
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
                'type' => $account->type->value,
                'currency' => $account->currency->value,
                'initial_balance' => $account->initial_balance,
                'balance' => bcsub(
                    bcadd((string) $account->initial_balance, (string) ($account->credits ?? 0), 4),
                    (string) ($account->debits ?? 0),
                    4,
                ),
                'is_archived' => $account->is_archived,
            ]);
    }
}
