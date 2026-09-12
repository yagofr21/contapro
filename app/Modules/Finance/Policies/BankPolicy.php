<?php

namespace App\Modules\Finance\Policies;

use App\Models\User;
use App\Modules\Finance\Models\Bank;

class BankPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Bank $bank): bool
    {
        return true;
    }

    public function delete(User $user, Bank $bank): bool
    {
        return true;
    }
}
