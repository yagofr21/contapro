<?php

namespace App\Modules\Finance\Support;

use App\Modules\Finance\Models\Installment;
use Carbon\CarbonInterface;

class InstallmentMath
{
    /**
     * Split a total (in cents) into affordable installments.
     * The last installment absorbs the rounding difference, so the sum
     * always matches the original total (e.g. 1000.00 in 3x = 333.33, 333.33, 333.34).
     *
     * @return array{base: int, last: int} Values in cents.
     */
    public static function split(int $totalCents, int $count): array
    {
        if ($count < 1) {
            throw new \InvalidArgumentException('Installment count must be at least 1.');
        }

        $base = intdiv($totalCents, $count);
        $last = $totalCents - ($base * ($count - 1));

        return ['base' => $base, 'last' => $last];
    }

    public static function amountToCents(string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public static function centsToAmount(int $cents): string
    {
        return number_format($cents / 100, 4, '.', '');
    }

    /** @return array<int, int> Ordinal => cents. */
    public static function amountsPerOrdinal(int $totalCents, int $count): array
    {
        $split = self::split($totalCents, $count);
        $amounts = [];

        for ($ordinal = 1; $ordinal <= $count; $ordinal++) {
            $amounts[$ordinal] = $ordinal < $count ? $split['base'] : $split['last'];
        }

        return $amounts;
    }

    /** @return list<CarbonInterface> */
    public static function dueDates(CarbonInterface $first, int $count): array
    {
        $dates = [];
        $cursor = $first;

        for ($i = 0; $i < $count; $i++) {
            $dates[] = $cursor;
            $cursor = $cursor->copy()->addMonthsNoOverflow(1);
        }

        return $dates;
    }

    /**
     * Full projection of the installment schedule (past + future).
     *
     * @return list<array{number: int, due_date: CarbonInterface, amount: string, status: 'pago'|'pendente'}>
     */
    public static function schedule(Installment $installment): array
    {
        $count = $installment->total_count;
        $paidCount = $installment->paidCount();
        $totalCents = InstallmentMath::amountToCents($installment->totalAmountValue());
        $amounts = InstallmentMath::amountsPerOrdinal($totalCents, $count);
        $firstDue = $installment->next_due_date->subMonths($paidCount);
        $dates = InstallmentMath::dueDates($firstDue, $count);

        $rows = [];
        foreach (range(1, $count) as $ordinal) {
            $rows[] = [
                'number' => $ordinal,
                'due_date' => $dates[$ordinal - 1],
                'amount' => InstallmentMath::centsToAmount($amounts[$ordinal]),
                'status' => $ordinal <= $paidCount ? 'pago' : 'pendente',
            ];
        }

        return $rows;
    }
}
