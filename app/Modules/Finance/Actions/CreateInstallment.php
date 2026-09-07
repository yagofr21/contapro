<?php

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Finance\Models\Installment;
use Carbon\CarbonImmutable;

class CreateInstallment
{
    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data): Installment
    {
        return $user->installments()->create([
            ...$data,
            'remaining_count' => (int) $data['total_count'],
            'next_due_date' => CarbonImmutable::parse($data['starts_on'])->format('Y-m-d'),
        ]);
    }
}
