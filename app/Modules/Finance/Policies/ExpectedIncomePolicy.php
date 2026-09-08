<?php

namespace App\Modules\Finance\Policies;

use App\Models\User;
use App\Modules\Finance\Models\ExpectedIncome;

class ExpectedIncomePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function receive(User $user, ExpectedIncome $expectedIncome): bool
    {
        return $expectedIncome->user_id === $user->id;
    }

    public function delete(User $user, ExpectedIncome $expectedIncome): bool
    {
        return $expectedIncome->user_id === $user->id;
    }
}
