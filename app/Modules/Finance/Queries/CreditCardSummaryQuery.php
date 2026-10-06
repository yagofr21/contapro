<?php

namespace App\Modules\Finance\Queries;

use App\Models\User;
use App\Modules\Finance\Enums\FinancialAccountType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Support\InstallmentMath;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CreditCardSummaryQuery
{
    /**
     * @return Collection<int|string, array{invoices: list<array<string, string>>, ...<string, mixed>}> Keyed by account id.
     */
    public function forUser(User $user, ?CarbonImmutable $today = null): Collection
    {
        return $user->financialAccounts()
            ->where('type', FinancialAccountType::CreditCard->value)
            ->orderBy('name')
            ->get()
            ->map(fn (FinancialAccount $card): array => $this->forAccount($card, $today))
            ->keyBy('id');
    }

    /** @return array{invoices: list<array<string, string>>, ...<string, mixed>} */
    public function forAccount(FinancialAccount $card, ?CarbonImmutable $today = null): array
    {
        $today = ($today ?? CarbonImmutable::now())->startOfDay();
        [$start, $end] = [$this->invoicePeriodStart($card, $today), $this->invoicePeriodEnd($card, $today)];
        $due = $this->dueFor($card, $end);
        $limitCents = $card->credit_limit !== null ? InstallmentMath::amountToCents($card->credit_limit) : null;

        $obligations = $this->obligations($card, $today);
        $allocation = $this->allocatePayments($obligations, $card);
        $remaining = $allocation['remaining'];
        $creditCents = $allocation['creditCents'];

        $overdueCents = 0;
        $closedCents = 0;
        $currentCents = 0;
        $futureCents = 0;
        $futureByMonth = [];

        foreach ($remaining as $row) {
            if ($row['remaining_cents'] <= 0) {
                continue;
            }

            if ($row['due_date']->lt($today)) {
                $overdueCents += $row['remaining_cents'];
            } elseif ($row['cycle_end']->lt($start)) {
                $closedCents += $row['remaining_cents'];
            } elseif ($row['cycle_end']->between($start, $end)) {
                $currentCents += $row['remaining_cents'];
            } else {
                $futureCents += $row['remaining_cents'];
                $month = $row['cycle_end']->format('Y-m');
                $futureByMonth[$month] = ($futureByMonth[$month] ?? 0) + $row['remaining_cents'];
            }
        }

        ksort($futureByMonth);

        $debtCents = $overdueCents + $closedCents + $currentCents + $futureCents;
        $availableCents = $limitCents !== null ? max(0, $limitCents - $debtCents) : null;
        $utilization = $limitCents !== null && $limitCents > 0
            ? (int) round($debtCents / $limitCents * 100)
            : null;

        $invoices = $this->invoices($remaining, $today);
        $suggested = collect($invoices)->first(fn (array $invoice): bool => $invoice['status'] !== 'paid' && $invoice['cycle'] < $today->format('Y-m-d'))
            ?? collect($invoices)->first(fn (array $invoice): bool => $invoice['status'] !== 'paid');

        return [
            'invoices' => $invoices,
            'closed_balance' => InstallmentMath::centsToAmount($closedCents),
            'suggested_invoice_cycle' => $suggested['cycle'] ?? null,
            'suggested_payment' => $suggested['amount'] ?? '0.0000',
            'suggested_due' => $suggested['due_date'] ?? null,
            'id' => $card->id,
            'name' => $card->name,
            'currency' => $card->currency->value,
            'has_limit' => $limitCents !== null,
            'credit_limit' => $limitCents !== null ? InstallmentMath::centsToAmount($limitCents) : null,
            'initial_debt' => InstallmentMath::centsToAmount($this->initialDebtCents($card)),
            'overdue_balance' => InstallmentMath::centsToAmount($overdueCents),
            'current_invoice' => InstallmentMath::centsToAmount($currentCents),
            'future_invoices' => InstallmentMath::centsToAmount($futureCents),
            'total_debt' => InstallmentMath::centsToAmount($debtCents),
            'utilized' => $limitCents !== null ? InstallmentMath::centsToAmount($debtCents) : null,
            'available' => $availableCents !== null ? InstallmentMath::centsToAmount($availableCents) : null,
            'credit_balance' => InstallmentMath::centsToAmount($creditCents),
            'over_limit' => $limitCents !== null && $debtCents > $limitCents,
            'utilization' => $utilization,
            'current_invoice_start' => $start->format('Y-m-d'),
            'current_invoice_end' => $end->format('Y-m-d'),
            'next_closing' => $end->format('Y-m-d'),
            'next_due' => $due->format('Y-m-d'),
            'status' => $overdueCents > 0 ? 'overdue' : ($closedCents > 0 ? (($suggested && CarbonImmutable::parse($suggested['due_date'])->diffInDays($today, false) >= -3) ? 'due_soon' : 'closed') : $this->status($overdueCents, $due, $end, $today)),
            'future_by_month' => array_map(
                fn (string $month, int $cents): array => ['month' => $month, 'amount' => InstallmentMath::centsToAmount($cents)],
                array_keys(array_slice($futureByMonth, 0, 6, true)),
                array_slice($futureByMonth, 0, 6, true),
            ),
            'current_purchases' => $this->serializeRows($remaining, $start, $end, ['purchase', 'installment'], false),
            'current_refunds' => $this->serializeRows($remaining, $start, $end, ['refund'], false),
            'future_installments' => $this->serializeRows($remaining, $end->addDay(), null, ['installment'], false),
            'payments' => $this->payments($card),
            'history' => $this->history($remaining, $end),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function obligations(FinancialAccount $card, CarbonImmutable $today): array
    {
        $rows = [];
        $initialDebt = $this->initialDebtCents($card);

        if ($initialDebt > 0) {
            $registered = CarbonImmutable::parse($card->created_at)->startOfDay()->min($today);
            $cycleEnd = $this->invoicePeriodStart($card, $registered)->subDay();
            $rows[] = [
                'date' => $cycleEnd,
                'cycle_end' => $cycleEnd,
                'due_date' => $this->dueFor($card, $cycleEnd),
                'kind' => 'initial_debt',
                'description' => 'Dívida inicial do cartão',
                'amount_cents' => $initialDebt,
                'remaining_cents' => $initialDebt,
            ];
        }

        foreach ($card->transactions()->whereIn('type', [TransactionType::Expense->value, TransactionType::Income->value])->orderBy('transaction_date')->orderBy('id')->get() as $transaction) {
            $date = CarbonImmutable::parse($transaction->transaction_date)->startOfDay();
            $cycleEnd = $this->cycleEndForDate($card, $date);
            $amountCents = InstallmentMath::amountToCents($transaction->amount);

            $rows[] = [
                'date' => $date,
                'cycle_end' => $cycleEnd,
                'due_date' => $this->dueFor($card, $cycleEnd),
                'kind' => $transaction->type === TransactionType::Income ? 'refund' : 'purchase',
                'description' => $transaction->description ?: ($transaction->type === TransactionType::Income ? 'Estorno' : 'Compra'),
                'amount_cents' => $transaction->type === TransactionType::Income ? -$amountCents : $amountCents,
                'remaining_cents' => $transaction->type === TransactionType::Income ? -$amountCents : $amountCents,
            ];
        }

        foreach ($card->installments()->where('remaining_count', '>', 0)->orderBy('next_due_date')->orderBy('id')->get() as $installment) {
            $ordinal = $installment->currentParcela();
            $date = CarbonImmutable::parse($installment->next_due_date)->startOfDay();

            while ($ordinal !== null && $ordinal <= $installment->total_count) {
                $cycleEnd = $this->cycleEndForDate($card, $date);
                $amountCents = InstallmentMath::amountToCents($installment->amountForOrdinal($ordinal));
                $rows[] = [
                    'date' => $date,
                    'cycle_end' => $cycleEnd,
                    'due_date' => $this->dueFor($card, $cycleEnd),
                    'kind' => 'installment',
                    'description' => sprintf('%s (%d/%d)', $installment->description ?: 'Parcela', $ordinal, $installment->total_count),
                    'amount_cents' => $amountCents,
                    'remaining_cents' => $amountCents,
                ];

                $date = $date->addMonthsNoOverflow(1);
                $ordinal++;
            }
        }

        usort($rows, fn (array $a, array $b): int => [$a['cycle_end']->timestamp, $a['date']->timestamp] <=> [$b['cycle_end']->timestamp, $b['date']->timestamp]);

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{remaining: list<array<string, mixed>>, creditCents: int}
     */
    private function allocatePayments(array $rows, FinancialAccount $card): array
    {
        $generalCredit = 0;
        foreach ($rows as &$row) {
            if ($row['remaining_cents'] < 0) {
                $generalCredit += abs($row['remaining_cents']);
                $row['remaining_cents'] = 0;
            }
        }
        unset($row);
        $payments = $card->transactions()->where('type', TransactionType::TransferIn->value)
            ->orderBy('transaction_date')->orderBy('id')->get();
        foreach ($payments as $payment) {
            $amount = InstallmentMath::amountToCents($payment->amount);
            if ($payment->invoice_cycle === null) {
                $generalCredit += $amount;

                continue;
            }
            $cycle = $payment->invoice_cycle->format('Y-m-d');
            foreach ($rows as &$row) {
                if ($row['cycle_end']->format('Y-m-d') !== $cycle || $row['remaining_cents'] <= 0) {
                    continue;
                }
                $paid = min($amount, $row['remaining_cents']);
                $row['remaining_cents'] -= $paid;
                $amount -= $paid;
            }
            unset($row);
            $generalCredit += $amount;
        }
        foreach ($rows as &$row) {
            $paid = min($generalCredit, max(0, $row['remaining_cents']));
            $row['remaining_cents'] -= $paid;
            $generalCredit -= $paid;
        }
        unset($row);

        return ['remaining' => $rows, 'creditCents' => max(0, $generalCredit)];
    }

    /** @param list<array<string, mixed>> $rows
     * @return list<array<string, string>>
     */
    private function invoices(array $rows, CarbonImmutable $today): array
    {
        return collect($rows)->groupBy(fn (array $row): string => $row['cycle_end']->format('Y-m-d'))
            ->map(function (Collection $items, string $cycle) use ($today): array {
                $remaining = $items->reduce(fn (int $sum, array $row): int => $sum + max(0, $row['remaining_cents']), 0);
                $due = $items->first()['due_date'];
                $status = $remaining === 0 ? 'paid' : ($due->lt($today) ? 'overdue' : ($cycle < $today->format('Y-m-d') ? 'closed' : 'open'));

                return ['cycle' => $cycle, 'start' => CarbonImmutable::parse($cycle)->subMonthNoOverflow()->addDay()->format('Y-m-d'),
                    'due_date' => $due->format('Y-m-d'), 'amount' => InstallmentMath::centsToAmount($remaining), 'status' => $status];
            })->sortBy('cycle')->values()->all();
    }

    private function initialDebtCents(FinancialAccount $card): int
    {
        return abs(InstallmentMath::amountToCents($card->initial_balance));
    }

    /** @return list<array{date: string, amount: string, description: string|null}> */
    private function payments(FinancialAccount $card): array
    {
        return $card->transactions()
            ->where('type', TransactionType::TransferIn->value)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(12)
            ->get(['transaction_date', 'amount', 'description'])
            ->map(fn (Transaction $payment): array => [
                'date' => $payment->transaction_date->format('Y-m-d'),
                'amount' => $payment->amount,
                'description' => $payment->description,
            ])
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $kinds
     * @return list<array<string, string>>
     */
    private function serializeRows(array $rows, CarbonImmutable $start, ?CarbonImmutable $end, array $kinds, bool $includeZero = true): array
    {
        return collect($rows)
            ->filter(fn (array $row): bool => in_array($row['kind'], $kinds, true))
            ->filter(fn (array $row): bool => $row['cycle_end']->gte($start) && ($end === null || $row['cycle_end']->lte($end)))
            ->filter(fn (array $row): bool => $includeZero || $row['remaining_cents'] !== 0)
            ->map(fn (array $row): array => [
                'date' => $row['date']->format('Y-m-d'),
                'cycle_end' => $row['cycle_end']->format('Y-m-d'),
                'due_date' => $row['due_date']->format('Y-m-d'),
                'kind' => $row['kind'],
                'description' => $row['description'],
                'amount' => InstallmentMath::centsToAmount(abs($row['amount_cents'])),
                'remaining' => InstallmentMath::centsToAmount(max(0, $row['remaining_cents'])),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{cycle: string, due_date: string, amount: string}>
     */
    private function history(array $rows, CarbonImmutable $currentEnd): array
    {
        return collect($rows)
            ->filter(fn (array $row): bool => $row['cycle_end']->lt($currentEnd))
            ->groupBy(fn (array $row): string => $row['cycle_end']->format('Y-m-d'))
            ->map(fn (Collection $items, string $cycle): array => [
                'cycle' => $cycle,
                'due_date' => $items->first()['due_date']->format('Y-m-d'),
                'amount' => InstallmentMath::centsToAmount($items->reduce(fn (int $total, array $row): int => $total + max(0, $row['remaining_cents']), 0)),
            ])
            ->values()
            ->all();
    }

    private function status(int $overdueCents, CarbonImmutable $due, CarbonImmutable $closing, CarbonImmutable $today): string
    {
        if ($overdueCents > 0) {
            return 'overdue';
        }

        if ($due->diffInDays($today, false) >= -3 && $today->lte($due)) {
            return 'due_soon';
        }

        if ($closing->diffInDays($today, false) >= -3 && $today->lte($closing)) {
            return 'closing_soon';
        }

        return 'open';
    }

    private function cycleEndForDate(FinancialAccount $card, CarbonImmutable $date): CarbonImmutable
    {
        $closing = $this->closingFor($card, $date);

        return $date->lte($closing) ? $closing : $this->closingFor($card, $date->addMonthsNoOverflow(1));
    }

    private function invoicePeriodStart(FinancialAccount $card, CarbonImmutable $today): CarbonImmutable
    {
        return $this->invoicePeriodEnd($card, $today)->subMonthNoOverflow()->addDay();
    }

    private function invoicePeriodEnd(FinancialAccount $card, CarbonImmutable $today): CarbonImmutable
    {
        return $this->cycleEndForDate($card, $today);
    }

    private function closingFor(FinancialAccount $card, CarbonImmutable $month): CarbonImmutable
    {
        $day = $card->credit_closing_day ?? 31;

        return $month->startOfMonth()->addDays(min($day, $month->daysInMonth) - 1);
    }

    private function dueFor(FinancialAccount $card, CarbonImmutable $invoiceEnd): CarbonImmutable
    {
        $day = $card->credit_due_day ?? 10;
        $base = $invoiceEnd->startOfMonth();
        $due = $base->addDays(min($day, $base->daysInMonth) - 1);

        return $due->lessThanOrEqualTo($invoiceEnd) ? $due->addMonthsNoOverflow(1) : $due;
    }
}
