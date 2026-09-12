<?php

namespace App\Modules\Finance\Queries;

use App\Models\User;
use App\Modules\Finance\Enums\FinancialAccountType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Support\InstallmentMath;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CreditCardSummaryQuery
{
    /**
     * Computes the credit usage of every credit card account.
     *
     * Utilized limit = current open invoice (expenses inside the invoice
     * period) + all future installments not due yet. The available limit
     * recovers automatically once an invoice closes.
     *
     * @return Collection<int, array<string, mixed>> Keyed by account id.
     */
    public function forUser(User $user, ?CarbonImmutable $today = null): Collection
    {
        $cards = $user->financialAccounts()
            ->where('type', FinancialAccountType::CreditCard->value)
            ->orderBy('name')
            ->get();

        return $cards
            ->map(fn (FinancialAccount $card): array => $this->forAccount($card, $today))
            ->keyBy('id');
    }

    /** @return array<string, mixed> */
    public function forAccount(FinancialAccount $card, ?CarbonImmutable $today = null): array
    {
        $today = ($today ?? CarbonImmutable::now())->startOfDay();
        [$start, $end] = [$this->invoicePeriodStart($card, $today), $this->invoicePeriodEnd($card, $today)];

        $usage = $this->usage($card, $start, $end);
        $currentCents = $usage['currentCents'];
        $futureCents = $usage['futureCents'];
        $futureByMonth = $usage['futureByMonth'];

        $limitCents = $card->credit_limit !== null
            ? InstallmentMath::amountToCents($card->credit_limit)
            : null;

        $utilization = $limitCents !== null && $limitCents > 0
            ? (int) round(($currentCents + $futureCents) / $limitCents * 100)
            : null;

        return [
            'id' => $card->id,
            'name' => $card->name,
            'has_limit' => $limitCents !== null,
            'credit_limit' => $limitCents !== null ? InstallmentMath::centsToAmount($limitCents) : null,
            'utilized' => $limitCents !== null
                ? InstallmentMath::centsToAmount($currentCents + $futureCents)
                : null,
            'available' => $limitCents !== null
                ? InstallmentMath::centsToAmount(max(0, $limitCents - $currentCents - $futureCents))
                : null,
            'over_limit' => $limitCents !== null && ($currentCents + $futureCents) > $limitCents,
            'utilization' => $utilization,
            'current_invoice' => InstallmentMath::centsToAmount($currentCents),
            'future_invoices' => InstallmentMath::centsToAmount($futureCents),
            'current_invoice_start' => $start->format('Y-m-d'),
            'current_invoice_end' => $end->format('Y-m-d'),
            'next_closing' => $end->format('Y-m-d'),
            'next_due' => $this->dueFor($card, $end)->format('Y-m-d'),
            'future_by_month' => $futureByMonth,
        ];
    }

    /** @return array{currentCents: int, futureCents: int, futureByMonth: list<array{month: string, amount: string}>} */
    private function usage(FinancialAccount $card, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $currentCents = 0;
        $futureCents = 0;
        $futureByMonth = [];

        foreach ($card->transactions()->where('type', TransactionType::Expense->value)->get(['transaction_date', 'amount']) as $movement) {
            $date = $movement->transaction_date->startOfDay();
            $cents = InstallmentMath::amountToCents($movement->amount);

            if ($date->between($start, $end)) {
                $currentCents += $cents;
            } elseif ($date->greaterThan($end)) {
                $futureCents += $cents;
                $month = $date->format('Y-m');
                $futureByMonth[$month] = ($futureByMonth[$month] ?? 0) + $cents;
            }
        }

        foreach ($card->installments()->where('remaining_count', '>', 0)->get() as $installment) {
            $ordinal = $installment->currentParcela();
            $nextDue = $installment->next_due_date->startOfDay();

            while ($ordinal !== null && $ordinal <= $installment->total_count) {
                $cents = InstallmentMath::amountToCents($installment->amountForOrdinal($ordinal));

                if ($nextDue->between($start, $end)) {
                    $currentCents += $cents;
                } elseif ($nextDue->greaterThan($end)) {
                    $futureCents += $cents;
                    $month = $nextDue->format('Y-m');
                    $futureByMonth[$month] = ($futureByMonth[$month] ?? 0) + $cents;
                }

                $nextDue = $nextDue->copy()->addMonthsNoOverflow(1);
                $ordinal++;
            }
        }

        ksort($futureByMonth);

        $futureByMonth = array_slice($futureByMonth, 0, 6, true);

        return [
            'currentCents' => $currentCents,
            'futureCents' => $futureCents,
            'futureByMonth' => array_map(
                fn (string $month, int $cents): array => [
                    'month' => $month,
                    'amount' => InstallmentMath::centsToAmount($cents),
                ],
                array_keys($futureByMonth),
                $futureByMonth,
            ),
        ];
    }

    private function invoicePeriodStart(FinancialAccount $card, CarbonImmutable $today): CarbonImmutable
    {
        $closing = $this->closingFor($card, $today);

        if ($today->isSameDay($closing) || $today->lessThan($closing)) {
            return $this->closingFor($card, $today->subMonthsNoOverflow(1))->copy()->addDay();
        }

        return $closing->copy()->addDay();
    }

    private function invoicePeriodEnd(FinancialAccount $card, CarbonImmutable $today): CarbonImmutable
    {
        $closing = $this->closingFor($card, $today);

        if ($today->isSameDay($closing) || $today->lessThan($closing)) {
            return $closing;
        }

        return $this->closingFor($card, $today->addMonthsNoOverflow(1));
    }

    private function closingFor(FinancialAccount $card, CarbonImmutable $month): CarbonImmutable
    {
        $day = $card->credit_closing_day ?? 31;

        return $month->copy()
            ->startOfMonth()
            ->addDays(min($day, $month->daysInMonth) - 1);
    }

    private function dueFor(FinancialAccount $card, CarbonImmutable $invoiceEnd): CarbonImmutable
    {
        $day = $card->credit_due_day ?? 10;
        $base = $invoiceEnd->copy()->startOfMonth();
        $due = $base->addDays(min($day, $base->daysInMonth) - 1);

        if ($due->lessThanOrEqualTo($invoiceEnd)) {
            $due = $due->addMonthsNoOverflow(1);
        }

        return $due;
    }
}
