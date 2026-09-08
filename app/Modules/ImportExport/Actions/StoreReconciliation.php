<?php

namespace App\Modules\ImportExport\Actions;

use App\Models\User;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\ImportExport\Enums\ReconciliationStatus;
use App\Modules\ImportExport\Models\Reconciliation;
use App\Modules\ImportExport\Support\CsvReader;
use App\Modules\ImportExport\Support\DecimalParser;
use App\Modules\ImportExport\Support\StatementMatcher;
use App\Modules\ImportExport\Support\StrictDateParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StoreReconciliation
{
    public function __construct(
        private CsvReader $reader,
        private DecimalParser $decimalParser,
        private StrictDateParser $dateParser,
        private StatementMatcher $matcher,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $user, FinancialAccount $account, array $data, UploadedFile $file): Reconciliation
    {
        $csv = $this->reader->read($file);
        $this->validateHeaders($csv['headers']);

        $periodStart = (string) $data['period_start'];
        $statementDate = (string) $data['statement_date'];
        $declaredBalance = (string) $data['declared_balance'];
        $declared = [];

        foreach ($csv['rows'] as $index => $raw) {
            $declared[] = $this->parseRow($index, $raw);
        }

        $validRows = [];

        foreach ($declared as $entry) {
            if ($entry['errors'] !== []) {
                continue;
            }

            $validRows[] = [
                'row_number' => $entry['row_number'],
                'date' => $entry['date'],
                'amount' => $entry['amount'],
                'description' => $entry['description'],
            ];
        }

        return DB::transaction(function () use (
            $user,
            $account,
            $periodStart,
            $statementDate,
            $declaredBalance,
            $declared,
            $validRows,
        ): Reconciliation {
            $result = $this->matcher->handle($user, $account, $periodStart, $statementDate, $validRows);

            $detected = $result['detected_balance'];
            $delta = bcsub($declaredBalance, $detected, 4);
            $summary = ['total' => count($declared), 'invalid' => 0, 'matched' => 0, 'missing' => 0, 'extras' => count($result['extras'])];

            foreach ($declared as $entry) {
                if ($entry['errors'] !== []) {
                    $summary['invalid']++;
                }
            }

            foreach ($result['rows'] as $row) {
                $summary[$row['status']]++;
            }

            $reconciliation = Reconciliation::query()->create([
                'public_id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'account_id' => $account->id,
                'kind' => 'financial',
                'status' => ReconciliationStatus::Previewed,
                'period_start' => $periodStart,
                'statement_date' => $statementDate,
                'declared_balance' => $declaredBalance,
                'detected_balance' => $detected,
                'delta' => $delta,
                'summary' => $summary,
                'confirmed_at' => null,
            ]);

            $cursor = 0;

            foreach ($declared as $entry) {
                $match = null;

                if ($entry['errors'] === []) {
                    $match = $result['rows'][$cursor];
                    $cursor++;
                }

                $reconciliation->rows()->create([
                    'row_number' => $entry['row_number'],
                    'raw' => $entry['raw'],
                    'entry_date' => $entry['date'] !== '' ? $entry['date'] : null,
                    'amount' => $entry['amount'],
                    'description' => $entry['description'],
                    'status' => $entry['errors'] !== [] ? 'invalid' : $match['status'],
                    'match_rule' => $match !== null ? $match['match_rule'] : null,
                    'matched_transaction_id' => $match !== null ? $match['transaction_id'] : null,
                    'errors' => $entry['errors'],
                ]);
            }

            return $reconciliation->refresh();
        });
    }

    /**
     * @param  array<string, string>  $raw
     * @return array{row_number: int, date: string, amount: string, description: string|null, errors: list<string>, raw: array<string, string>}
     */
    private function parseRow(int $index, array $raw): array
    {
        $errors = [];
        $date = '';
        $amount = '0.0000';

        try {
            $date = $this->dateParser->parse($this->value($raw, 'Data'));
        } catch (InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();
        }

        try {
            $amount = $this->decimalParser->parse($this->value($raw, 'Valor'), 4, 15);
        } catch (InvalidArgumentException $exception) {
            $errors[] = 'Valor: '.$exception->getMessage();
        }

        if ($errors === [] && bccomp($amount, '0', 4) === 0) {
            $errors[] = 'Valor deve ser diferente de zero.';
        }

        return [
            'row_number' => $index + 2,
            'date' => $date,
            'amount' => $amount,
            'description' => $this->value($raw, 'Descricao') ?: null,
            'errors' => $errors,
            'raw' => $raw,
        ];
    }

    /** @param array<string, string> $raw */
    private function value(array $raw, string $header): string
    {
        $target = mb_strtolower(Str::ascii(trim($header)));

        foreach ($raw as $key => $value) {
            if (mb_strtolower(Str::ascii(trim($key))) === $target) {
                $value = trim((string) $value);

                return preg_match('/^\'[=+\-@]/', $value) === 1 ? substr($value, 1) : $value;
            }
        }

        return '';
    }

    /** @param list<string> $headers */
    private function validateHeaders(array $headers): void
    {
        $normalized = array_map(
            fn (string $header) => mb_strtolower(Str::ascii(trim($header))),
            $headers,
        );

        if (count(array_unique($normalized)) !== count($normalized)) {
            throw ValidationException::withMessages(['file' => 'O CSV possui cabecalhos equivalentes ou duplicados.']);
        }

        $missing = array_diff(['data', 'descricao', 'valor'], $normalized);

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'file' => 'Cabecalhos obrigatorios ausentes: '.implode(', ', $missing).'.',
            ]);
        }
    }
}
