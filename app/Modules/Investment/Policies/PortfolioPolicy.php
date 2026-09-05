<?php

namespace App\Modules\Investment\Policies;

use App\Models\User;
use App\Modules\Investment\Models\Portfolio;

class PortfolioPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Portfolio $portfolio): bool
    {
        return $portfolio->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Portfolio $portfolio): bool
    {
        return $this->view($user, $portfolio);
    }

    public function delete(User $user, Portfolio $portfolio): bool
    {
        return $this->view($user, $portfolio);
    }
}
