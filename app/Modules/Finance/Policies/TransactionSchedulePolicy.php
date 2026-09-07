<?php

namespace App\Modules\Finance\Policies;

use App\Models\User;
use App\Modules\Finance\Models\TransactionSchedule;

class TransactionSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, TransactionSchedule $schedule): bool
    {
        return $schedule->user_id === $user->id;
    }

    public function update(User $user, TransactionSchedule $schedule): bool
    {
        return $this->view($user, $schedule);
    }

    public function delete(User $user, TransactionSchedule $schedule): bool
    {
        return $this->view($user, $schedule);
    }
}
