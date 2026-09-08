<?php

namespace App\Modules\ImportExport\Actions;

use App\Models\User;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\ImportExport\Enums\ReconciliationStatus;
use App\Modules\ImportExport\Models\Reconciliation;
use App\Modules\ImportExport\Support\StatementMatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmReconciliation
{
    public function __construct(
        private StatementMatcher $matcher,
    ) {}

    public function handle(User $user, Reconciliation $reconciliation): Reconciliation
    {
        return DB::transaction(function () use ($user, $reconciliation): Reconciliation {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $reconciliation = Reconciliation::query()
                ->whereKey($reconciliation->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($reconciliation->status !== ReconciliationStatus::Previewed) {
                return $reconciliation;
            }

            $account = $reconciliation->account;

            if (! $account instanceof FinancialAccount || $account->user_id !== $user->id) {
                throw ValidationException::withMessages(['account' => 'A conta desta conciliacao nao esta disponivel.']);
            }

            $rows = $reconciliation->rows()->orderBy('row_number')->get();
            $declared = [];

            foreach ($rows as $row) {
                if ($row->status === 'invalid' || $row->entry_date === null) {
                    continue;
                }

                $declared[] = [
                    'row_number' => $row->row_number,
                    'date' => $row->entry_date->format('Y-m-d'),
                    'amount' => $row->amount,
                    'description' => $row->description,
                ];
            }

            $result = $this->matcher->handle(
                $user,
                $account,
                $reconciliation->period_start->format('Y-m-d'),
                $reconciliation->statement_date->format('Y-m-d'),
                $declared,
            );

            $cursor = 0;

            foreach ($rows as $row) {
                if ($row->status === 'invalid' || $row->entry_date === null) {
                    continue;
                }

                $match = $result['rows'][$cursor];
                $cursor++;

                $row->update([
                    'status' => $match['status'],
                    'match_rule' => $match['match_rule'],
                    'matched_transaction_id' => $match['transaction_id'],
                ]);
            }

            $detected = $result['detected_balance'];
            $delta = bcsub($reconciliation->declared_balance, $detected, 4);

            $summary = [
                'total' => $rows->count(),
                'invalid' => $rows->where('status', 'invalid')->count(),
                'matched' => 0,
                'missing' => 0,
                'extras' => count($result['extras']),
            ];

            foreach ($result['rows'] as $row) {
                $summary[$row['status']]++;
            }

            $withinTolerance = bccomp(ltrim($delta, '-'), '0.01', 2) <= 0;
            $status = $withinTolerance && $summary['invalid'] === 0 && $summary['missing'] === 0 && $summary['extras'] === 0
                ? ReconciliationStatus::Reconciled
                : ReconciliationStatus::Divergent;

            $reconciliation->update([
                'status' => $status,
                'detected_balance' => $detected,
                'delta' => $delta,
                'summary' => $summary,
                'confirmed_at' => now(),
            ]);

            return $reconciliation->refresh();
        });
    }
}
