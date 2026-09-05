<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\FinancialAccount;

class UpdateFinancialAccount
{
    /** @param array<string, mixed> $data */
    public function handle(FinancialAccount $account, array $data): FinancialAccount
    {
        $account->update($data);

        return $account->refresh();
    }
}
