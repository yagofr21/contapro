<?php

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Finance\Models\Budget;

class CreateBudget
{
    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data): Budget
    {
        return $user->budgets()->create($data);
    }
}
