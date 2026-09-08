<?php

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Finance\Models\FinancialGoal;

class CreateFinancialGoal
{
    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data): FinancialGoal
    {
        return $user->financialGoals()->create($data);
    }
}
