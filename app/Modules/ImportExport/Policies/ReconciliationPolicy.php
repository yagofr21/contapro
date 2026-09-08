<?php

namespace App\Modules\ImportExport\Policies;

use App\Models\User;
use App\Modules\ImportExport\Models\Reconciliation;

class ReconciliationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Reconciliation $reconciliation): bool
    {
        return $reconciliation->user_id === $user->id;
    }

    public function update(User $user, Reconciliation $reconciliation): bool
    {
        return $this->view($user, $reconciliation);
    }

    public function delete(User $user, Reconciliation $reconciliation): bool
    {
        return $this->view($user, $reconciliation);
    }
}
