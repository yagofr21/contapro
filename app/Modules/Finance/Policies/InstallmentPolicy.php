<?php

namespace App\Modules\Finance\Policies;

use App\Models\User;
use App\Modules\Finance\Models\Installment;

class InstallmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Installment $installment): bool
    {
        return $installment->user_id === $user->id;
    }

    public function delete(User $user, Installment $installment): bool
    {
        return $this->view($user, $installment);
    }
}
