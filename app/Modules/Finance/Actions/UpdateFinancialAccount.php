<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\ExpectedIncome;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialGoal;
use App\Modules\Finance\Models\TransactionSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateFinancialAccount
{
    /** @param array<string, mixed> $data */
    public function handle(FinancialAccount $account, array $data): FinancialAccount
    {
        return DB::transaction(function () use ($account, $data): FinancialAccount {
            $account = FinancialAccount::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();

            if (isset($data['currency']) && $data['currency'] !== $account->currency->value
                && ($account->transactions()->exists()
                    || $account->installments()->exists()
                    || TransactionSchedule::query()->where(fn ($query) => $query
                        ->where('account_id', $account->id)
                        ->orWhere('destination_account_id', $account->id))->exists()
                    || FinancialGoal::query()->where('account_id', $account->id)->exists()
                    || ExpectedIncome::query()->where('account_id', $account->id)->exists())) {
                throw ValidationException::withMessages([
                    'currency' => 'A moeda não pode ser alterada enquanto a conta possui registros financeiros vinculados.',
                ]);
            }

            $account->update($data);

            return $account->refresh();
        });
    }
}
