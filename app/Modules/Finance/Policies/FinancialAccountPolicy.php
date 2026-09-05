<?php

namespace App\Modules\Finance\Policies;

use App\Models\User;
use App\Modules\Finance\Models\FinancialAccount;

class FinancialAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FinancialAccount $account): bool
    {
        return $account->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, FinancialAccount $account): bool
    {
        return $this->view($user, $account);
    }

    public function delete(User $user, FinancialAccount $account): bool
    {
        return $this->view($user, $account);
    }
}
