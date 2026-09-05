<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\Transaction;
use Illuminate\Support\Facades\DB;

class DeleteTransaction
{
    public function handle(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            if ($transaction->transfer_id === null) {
                $transaction->delete();

                return;
            }

            Transaction::query()
                ->where('user_id', $transaction->user_id)
                ->where('transfer_id', $transaction->transfer_id)
                ->delete();
        });
    }
}
