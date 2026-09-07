<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\TransactionSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class GenerateScheduledTransactions
{
    public function __construct(
        private readonly CreateTransaction $createTransaction,
    ) {}

    /**
     * Materializes every due transaction schedule into a real transaction.
     * Idempotent: advances next_run_date before returning, so concurrent
     * executions on the same day produce at most one transaction per schedule.
     *
     * @param  CarbonImmutable|null  $today  Reference date (injectable for tests).
     * @return int The number of transactions created.
     */
    public function handle(?CarbonImmutable $today = null): int
    {
        $today = ($today ?? CarbonImmutable::now())->startOfDay();
        $created = 0;

        TransactionSchedule::query()
            ->where('is_active', true)
            ->whereDate('next_run_date', '<=', $today->format('Y-m-d'))
            ->each(function (TransactionSchedule $schedule) use ($today, &$created): void {
                DB::transaction(function () use ($schedule, $today, &$created): void {
                    $schedule->refresh();

                    if (! $schedule->is_active || $schedule->next_run_date->gt($today)) {
                        return;
                    }

                    if ($schedule->ends_on !== null && $schedule->next_run_date->gt($schedule->ends_on)) {
                        $schedule->update(['is_active' => false]);

                        return;
                    }

                    $dueDate = $schedule->next_run_date->toImmutable();
                    $next = $schedule->frequency->advance($dueDate);

                    if ($schedule->ends_on !== null && $next->gt($schedule->ends_on)) {
                        $schedule->update(['is_active' => false, 'next_run_date' => $next->format('Y-m-d')]);
                    } else {
                        $schedule->update(['next_run_date' => $next->format('Y-m-d')]);
                    }

                    $this->createTransaction->handle($schedule->user, $this->transactionPayload($schedule, $dueDate));
                    $created++;
                });
            });

        return $created;
    }

    /** @return array<string, mixed> */
    private function transactionPayload(TransactionSchedule $schedule, CarbonImmutable $dueDate): array
    {
        return [
            'type' => $schedule->type->value,
            'account_id' => $schedule->account_id,
            'destination_account_id' => $schedule->destination_account_id,
            'category_id' => $schedule->category_id,
            'amount' => $schedule->amount,
            'transaction_date' => $dueDate->format('Y-m-d'),
            'description' => $schedule->description,
        ];
    }
}
