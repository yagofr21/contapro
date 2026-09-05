<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\Budget;

class UpdateBudget
{
    /** @param array<string, mixed> $data */
    public function handle(Budget $budget, array $data): Budget
    {
        $budget->update($data);

        return $budget->refresh();
    }
}
