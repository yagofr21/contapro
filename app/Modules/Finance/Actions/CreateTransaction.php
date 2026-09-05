<?php

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTransaction
{
    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($user, $data): Transaction {
            if ($data['type'] !== 'transfer') {
                return $user->transactions()->create([
                    ...$data,
                    'type' => TransactionType::from((string) $data['type']),
                ]);
            }

            $transferId = (string) Str::uuid();
            $shared = [
                'user_id' => $user->id,
                'category_id' => null,
                'transfer_id' => $transferId,
                'amount' => $data['amount'],
                'transaction_date' => $data['transaction_date'],
                'description' => $data['description'] ?? null,
            ];

            $outgoing = Transaction::query()->create([
                ...$shared,
                'account_id' => $data['account_id'],
                'type' => TransactionType::TransferOut,
            ]);

            Transaction::query()->create([
                ...$shared,
                'account_id' => $data['destination_account_id'],
                'type' => TransactionType::TransferIn,
            ]);

            return $outgoing;
        });
    }
}
