<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\FinancialGoal;

class UpdateFinancialGoal
{
    /** @param array<string, mixed> $data */
    public function handle(FinancialGoal $goal, array $data): FinancialGoal
    {
        $goal->update($data);

        return $goal->refresh();
    }
}
