<?php

namespace App\Modules\ImportExport\Actions;

use App\Models\User;
use App\Modules\Finance\Actions\CreateTransaction;
use App\Modules\ImportExport\Enums\ImportKind;
use App\Modules\ImportExport\Enums\ImportStatus;
use App\Modules\ImportExport\Models\ImportBatch;
use App\Modules\ImportExport\Models\ImportRow;
use App\Modules\Investment\Actions\CreateAssetTransaction;
use App\Modules\Investment\Models\Portfolio;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmCsvImport
{
    public function __construct(
        private CreateTransaction $createTransaction,
        private CreateAssetTransaction $createAssetTransaction,
    ) {}

    public function handle(User $user, ImportBatch $batch, bool $skipInvalid): ImportBatch
    {
        return DB::transaction(function () use ($user, $batch, $skipInvalid): ImportBatch {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $batch = ImportBatch::query()
                ->whereKey($batch->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($batch->status === ImportStatus::Confirmed) {
                return $batch;
            }

            $portfolio = $batch->portfolio;

            if ($batch->kind === ImportKind::Investment && ! $portfolio instanceof Portfolio) {
                throw ValidationException::withMessages(['portfolio' => 'A carteira desta importacao nao esta disponivel.']);
            }

            $invalidCount = $batch->rows()->where('status', 'invalid')->count();

            if ($invalidCount > 0 && ! $skipInvalid) {
                throw ValidationException::withMessages([
                    'skip_invalid' => 'Existem linhas invalidas. Revise o arquivo ou autorize ignora-las.',
                ]);
            }

            $rows = $batch->rows()->where('status', 'valid')->get();

            if ($rows->isEmpty()) {
                throw ValidationException::withMessages(['file' => 'Nao existem linhas validas para confirmar.']);
            }

            $duplicateCount = 0;

            foreach ($rows as $key => $row) {
                $alreadyImported = $row->fingerprint !== null && ImportRow::query()
                    ->where('fingerprint', $row->fingerprint)
                    ->whereKeyNot($row->id)
                    ->whereHas('batch', fn ($query) => $query
                        ->where('user_id', $user->id)
                        ->where('kind', $batch->kind->value)
                        ->where('status', ImportStatus::Confirmed->value))
                    ->exists();

                if ($alreadyImported) {
                    $row->update([
                        'status' => 'duplicate',
                        'errors' => ['Possivel duplicidade de uma linha ja importada.'],
                    ]);
                    $rows->forget($key);
                    $duplicateCount++;
                }
            }

            if ($batch->kind === ImportKind::Investment) {
                $rows = $rows->sortBy(fn ($row) => sprintf(
                    '%s-%08d',
                    $row->normalized['transaction_date'],
                    $row->row_number,
                ));
            }

            foreach ($rows as $row) {
                /** @var array<string, mixed> $data */
                $data = $row->normalized;
                $created = $batch->kind === ImportKind::Financial
                    ? $this->createTransaction->handle($user, $data)
                    : $this->createAssetTransaction->handle($portfolio, $data);
                $row->update([
                    'status' => 'imported',
                    'importable_type' => $created::class,
                    'importable_id' => $created->id,
                ]);
            }

            $summary = $batch->summary;
            $summary['imported'] = $rows->count();
            $summary['valid'] = max(0, (int) $summary['valid'] - $duplicateCount);
            $summary['duplicate'] = (int) $summary['duplicate'] + $duplicateCount;
            $batch->update([
                'status' => ImportStatus::Confirmed,
                'summary' => $summary,
                'confirmed_at' => now(),
            ]);

            return $batch->refresh();
        });
    }
}
