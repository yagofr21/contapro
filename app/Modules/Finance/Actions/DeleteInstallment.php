<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\Installment;

class DeleteInstallment
{
    public function handle(Installment $installment): void
    {
        $installment->delete();
    }
}
