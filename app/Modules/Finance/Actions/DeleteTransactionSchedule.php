<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\TransactionSchedule;

class DeleteTransactionSchedule
{
    public function handle(TransactionSchedule $schedule): void
    {
        $schedule->delete();
    }
}
