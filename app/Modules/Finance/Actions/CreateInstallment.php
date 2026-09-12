<?php

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Finance\Models\Installment;
use App\Modules\Finance\Support\InstallmentMath;
use Carbon\CarbonImmutable;

class CreateInstallment
{
    /**
     * Creates an installment series.
     *
     * Accepts either the per-installment value (legacy, amount + total_count)
     * or the full purchase total (total_amount + total_count). When the total
     * is provided the per-installment values are computed so the last parcel
     * absorbs the rounding difference and the sum equals the original total.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): Installment
    {
        $count = (int) $data['total_count'];
        $totalAmount = isset($data['total_amount'])
            ? (string) $data['total_amount']
            : bcadd(bcmul((string) $data['amount'], (string) $count, 4), '0', 4);

        $base = isset($data['total_amount'])
            ? InstallmentMath::centsToAmount(
                InstallmentMath::split(InstallmentMath::amountToCents($totalAmount), $count)['base'],
            )
            : (string) $data['amount'];

        return $user->installments()->create([
            ...$data,
            'amount' => bcadd($base, '0', 4),
            'total_amount' => bcadd($totalAmount, '0', 4),
            'remaining_count' => $count,
            'next_due_date' => CarbonImmutable::parse($data['starts_on'])->format('Y-m-d'),
        ]);
    }
}
