<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\Installment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ProcessInstallments
{
    public function __construct(
        private readonly CreateTransaction $createTransaction,
    ) {}

    /**
     * Materializes every due installment into a real transaction.
     * Idempotent: advances next_due_date before returning, so each due date
     * produces at most one transaction per installment.
     *
     * @param  CarbonImmutable|null  $today  Reference date (injectable for tests).
     * @return int The number of transactions created.
     */
    public function handle(?CarbonImmutable $today = null): int
    {
        $today = ($today ?? CarbonImmutable::now())->startOfDay();
        $created = 0;

        Installment::query()
            ->where('remaining_count', '>', 0)
            ->whereDate('next_due_date', '<=', $today->format('Y-m-d'))
            ->each(function (Installment $installment) use ($today, &$created): void {
                DB::transaction(function () use ($installment, $today, &$created): void {
                    $installment->refresh();

                    if ($installment->remaining_count <= 0 || $installment->next_due_date->gt($today)) {
                        return;
                    }

                    $this->createTransaction->handle($installment->user, [
                        'type' => $installment->type->value,
                        'account_id' => $installment->account_id,
                        'category_id' => $installment->category_id,
                        'amount' => $installment->amount,
                        'transaction_date' => $installment->next_due_date->format('Y-m-d'),
                        'description' => $this->description($installment),
                    ]);

                    $remaining = $installment->remaining_count - 1;

                    $installment->update([
                        'remaining_count' => $remaining,
                        'next_due_date' => $installment->next_due_date->copy()->addMonth()->format('Y-m-d'),
                    ]);

                    $created++;
                });
            });

        return $created;
    }

    private function description(Installment $installment): string
    {
        $paid = $installment->total_count - $installment->remaining_count + 1;
        $base = trim((string) $installment->description);

        return sprintf('%s (%d/%d)', $base !== '' ? $base : 'Parcela', $paid, $installment->total_count);
    }
}
