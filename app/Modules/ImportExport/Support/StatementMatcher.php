<?php

namespace App\Modules\ImportExport\Support;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Support\Carbon;

class StatementMatcher
{
    /** Tolerancia em dias para a regra de janela (data +/- 3 dias). */
    private const WINDOW_DAYS = 3;

    /**
     * @param  list<array{row_number: int, date: string, amount: string, description: string|null}>  $declared
     * @return array{
     *     rows: list<array{row_number: int, date: string, amount: string, description: string|null, status: string, match_rule: string|null, transaction_id: int|null}>,
     *     extras: list<array{id: int, type: string, amount: string, date: string, description: string|null}>,
     *     detected_balance: string
     * }
     */
    public function handle(User $user, FinancialAccount $account, string $periodStart, string $statementDate, array $declared): array
    {
        $pool = [];

        foreach ($this->periodTransactions($user, $account, $periodStart, $statementDate) as $transaction) {
            $pool[] = [
                'id' => $transaction->id,
                'type' => $this->stringify($transaction->type->value),
                'amount' => $this->stringify($transaction->amount),
                'date' => $transaction->transaction_date->format('Y-m-d'),
                'description' => $transaction->description,
            ];
        }

        $consumed = [];
        $rows = [];

        foreach ($declared as $row) {
            [$matchedKey, $rule] = $this->findMatch($pool, $consumed, $row);

            if ($matchedKey !== null) {
                $consumed[$matchedKey] = true;
            }

            $rows[] = [
                'row_number' => $row['row_number'],
                'date' => $row['date'],
                'amount' => $row['amount'],
                'description' => $row['description'],
                'status' => $matchedKey !== null ? 'matched' : 'missing',
                'match_rule' => $rule,
                'transaction_id' => $matchedKey !== null ? $pool[$matchedKey]['id'] : null,
            ];
        }

        $extras = [];

        foreach ($pool as $key => $candidate) {
            if (! isset($consumed[$key])) {
                $extras[] = $candidate;
            }
        }

        return [
            'rows' => $rows,
            'extras' => $extras,
            'detected_balance' => $this->detectedBalance($account, $statementDate),
        ];
    }

    /**
     * @param  array<int, array{id: int, type: string, amount: string, date: string, description: string|null}>  $pool
     * @param  array<int, true>  $consumed
     * @param  array{row_number: int, date: string, amount: string, description: string|null}  $row
     * @return array{int|null, string|null}
     */
    private function findMatch(array $pool, array $consumed, array $row): array
    {
        $amountNegative = str_starts_with($row['amount'], '-');
        $eligible = $amountNegative
            ? [TransactionType::Expense->value, TransactionType::TransferOut->value]
            : [TransactionType::Income->value, TransactionType::TransferIn->value];
        $absAmount = ltrim($row['amount'], '-');
        $exactKey = null;
        $windowKey = null;
        $windowDistance = PHP_INT_MAX;

        foreach ($pool as $key => $candidate) {
            if (isset($consumed[$key])) {
                continue;
            }

            if (! in_array($candidate['type'], $eligible, true)) {
                continue;
            }

            if (bccomp(ltrim($candidate['amount'], '-'), $absAmount, 4) !== 0) {
                continue;
            }

            if ($candidate['date'] === $row['date']) {
                $exactKey ??= $key;

                continue;
            }

            $distance = abs($this->dateDiffInDays($candidate['date'], $row['date']));

            if ($distance <= self::WINDOW_DAYS && $distance < $windowDistance) {
                $windowDistance = $distance;
                $windowKey = $key;
            }
        }

        if ($exactKey !== null) {
            return [$exactKey, 'exact'];
        }

        if ($windowKey !== null) {
            return [$windowKey, 'window'];
        }

        return [null, null];
    }

    /** @return array<int, Transaction> */
    private function periodTransactions(User $user, FinancialAccount $account, string $periodStart, string $statementDate): array
    {
        return $user->transactions()
            ->where('account_id', $account->id)
            ->whereBetween('transaction_date', [$periodStart, $statementDate])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->all();
    }

    private function detectedBalance(FinancialAccount $account, string $statementDate): string
    {
        $account->loadSum([
            'transactions as credits' => fn ($query) => $query->whereIn('type', [
                TransactionType::Income->value,
                TransactionType::TransferIn->value,
            ])->where('transaction_date', '<=', $statementDate),
        ], 'amount');
        $account->loadSum([
            'transactions as debits' => fn ($query) => $query->whereIn('type', [
                TransactionType::Expense->value,
                TransactionType::TransferOut->value,
            ])->where('transaction_date', '<=', $statementDate),
        ], 'amount');

        return bcsub(
            bcadd((string) $account->initial_balance, (string) ($account->credits ?? 0), 4),
            (string) ($account->debits ?? 0),
            4,
        );
    }

    private function dateDiffInDays(string $left, string $right): int
    {
        $leftDay = Carbon::parse($left)->startOfDay();
        $rightDay = Carbon::parse($right)->startOfDay();

        return (int) $leftDay->diffInDays($rightDay);
    }

    private function stringify(mixed $value): string
    {
        return (string) $value;
    }
}
