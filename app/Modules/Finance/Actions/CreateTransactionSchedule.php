<?php

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Finance\Models\TransactionSchedule;
use Carbon\CarbonImmutable;

class CreateTransactionSchedule
{
    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data): TransactionSchedule
    {
        return $user->transactionSchedules()->create([
            ...$data,
            'next_run_date' => CarbonImmutable::parse($data['starts_on'])->format('Y-m-d'),
        ]);
    }
}
