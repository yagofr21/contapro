<?php

namespace App\Modules\Finance\Policies;

use App\Models\User;
use App\Modules\Finance\Models\Budget;

class BudgetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Budget $budget): bool
    {
        return $budget->user_id === $user->id;
    }

    public function delete(User $user, Budget $budget): bool
    {
        return $this->update($user, $budget);
    }
}
