<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\TransactionSchedule;
use Carbon\CarbonImmutable;

class UpdateTransactionSchedule
{
    /** @param array<string, mixed> $data */
    public function handle(TransactionSchedule $schedule, array $data): TransactionSchedule
    {
        $updates = $data;

        if (isset($data['starts_on'])) {
            $updates['next_run_date'] = CarbonImmutable::parse($data['starts_on'])->format('Y-m-d');
        }

        $schedule->update($updates);

        return $schedule->refresh();
    }
}
