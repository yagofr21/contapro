<?php

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Finance\Models\FinancialAccount;

class CreateFinancialAccount
{
    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data): FinancialAccount
    {
        return $user->financialAccounts()->create($data);
    }
}
