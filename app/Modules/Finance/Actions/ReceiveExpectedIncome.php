<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\ExpectedIncome;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ReceiveExpectedIncome
{
    public function handle(ExpectedIncome $expectedIncome): Transaction
    {
        return DB::transaction(function () use ($expectedIncome): Transaction {
            $locked = ExpectedIncome::query()
                ->lockForUpdate()
                ->findOrFail($expectedIncome->id);

            if ($locked->received) {
                return $locked->transaction ?? throw new \LogicException('Recebimento sem lancamento vinculado.');
            }

            $transaction = app(CreateTransaction::class)->handle($locked->user, [
                'type' => TransactionType::Income->value,
                'account_id' => $locked->account_id,
                'category_id' => $locked->category_id,
                'amount' => $locked->amount,
                'transaction_date' => now()->toDateString(),
                'description' => $locked->description,
            ]);

            $locked->update([
                'received_at' => now(),
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }
}
