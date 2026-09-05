<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Support\Facades\DB;

class UpdateTransaction
{
    /** @param array<string, mixed> $data */
    public function handle(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data): Transaction {
            if ($transaction->transfer_id === null) {
                $transaction->update([
                    ...$data,
                    'type' => TransactionType::from((string) $data['type']),
                ]);

                return $transaction->refresh();
            }

            $pair = Transaction::query()
                ->where('user_id', $transaction->user_id)
                ->where('transfer_id', $transaction->transfer_id)
                ->get();

            foreach ($pair as $entry) {
                $entry->update([
                    'account_id' => $entry->type === TransactionType::TransferOut
                        ? $data['account_id']
                        : $data['destination_account_id'],
                    'amount' => $data['amount'],
                    'transaction_date' => $data['transaction_date'],
                    'description' => $data['description'] ?? null,
                ]);
            }

            return $pair->firstWhere('type', TransactionType::TransferOut)?->refresh()
                ?? $transaction->refresh();
        });
    }
}
