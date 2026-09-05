<?php

namespace App\Modules\Investment\Actions;

use App\Models\User;
use App\Modules\Investment\Models\Portfolio;

class CreatePortfolio
{
    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data): Portfolio
    {
        return $user->portfolios()->create($data);
    }
}
