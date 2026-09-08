<?php

namespace App\Modules\Finance\Policies;

use App\Models\User;
use App\Modules\Finance\Models\FinancialGoal;

class FinancialGoalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, FinancialGoal $goal): bool
    {
        return $goal->user_id === $user->id;
    }

    public function delete(User $user, FinancialGoal $goal): bool
    {
        return $this->update($user, $goal);
    }
}
