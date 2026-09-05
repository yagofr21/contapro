<?php

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Finance\Models\Category;

class CreateCategory
{
    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data): Category
    {
        return $user->categories()->create($data);
    }
}
