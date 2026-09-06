<?php

namespace App\Modules\Investment\Policies;

use App\Models\User;
use App\Modules\Investment\Models\Broker;

class BrokerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Broker $broker): bool
    {
        return $broker->user_id === $user->id;
    }
}
