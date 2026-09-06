<?php

namespace App\Modules\ImportExport\Actions;

use App\Models\User;
use App\Modules\ImportExport\Enums\ImportKind;
use App\Modules\ImportExport\Enums\ImportStatus;
use App\Modules\ImportExport\Models\ImportBatch;
use App\Modules\ImportExport\Models\ImportRow;
use App\Modules\ImportExport\Support\CsvReader;
use App\Modules\Investment\Models\Portfolio;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreCsvImport
{
    public function __construct(
        private CsvReader $reader,
        private NormalizeImportRow $normalizer,
    ) {}

    public function handle(User $user, ImportKind $kind, UploadedFile $file, ?Portfolio $portfolio): ImportBatch
    {
        $csv = $this->reader->read($file);
        $this->validateHeaders($kind, $csv['headers']);

        return DB::transaction(function () use ($user, $kind, $file, $portfolio, $csv): ImportBatch {
            $batch = ImportBatch::query()->create([
                'public_id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'portfolio_id' => $portfolio?->id,
                'kind' => $kind,
                'status' => ImportStatus::Previewed,
                'original_filename' => mb_substr($file->getClientOriginalName(), 0, 255),
                'file_sha256' => hash_file('sha256', $file->getPathname()),
                'summary' => [],
            ]);
            $seen = [];
            $summary = ['total' => 0, 'valid' => 0, 'invalid' => 0, 'duplicate' => 0, 'imported' => 0];

            foreach ($csv['rows'] as $index => $raw) {
                $result = $this->normalizer->handle($user, $kind, $raw, $portfolio);
                $fingerprint = $result['normalized'] !== null
                    ? hash('sha256', json_encode($result['normalized'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE))
                    : null;
                $duplicate = $fingerprint !== null && (isset($seen[$fingerprint]) || ImportRow::query()
                    ->where('fingerprint', $fingerprint)
                    ->whereHas('batch', fn ($query) => $query
                        ->where('user_id', $user->id)
                        ->where('kind', $kind->value)
                        ->where('status', ImportStatus::Confirmed->value))
                    ->exists());
                $status = $result['normalized'] === null ? 'invalid' : ($duplicate ? 'duplicate' : 'valid');

                $batch->rows()->create([
                    'row_number' => $index + 2,
                    'raw' => $raw,
                    'normalized' => $result['normalized'],
                    'fingerprint' => $fingerprint,
                    'status' => $status,
                    'errors' => $duplicate ? ['Possivel duplicidade de uma linha ja importada.'] : $result['errors'],
                ]);
                $summary['total']++;
                $summary[$status]++;

                if ($fingerprint !== null) {
                    $seen[$fingerprint] = true;
                }
            }

            $batch->update(['summary' => [
                ...$summary,
                'delimiter' => $csv['delimiter'] === "\t" ? 'tab' : $csv['delimiter'],
            ]]);

            return $batch;
        });
    }

    /** @param list<string> $headers */
    private function validateHeaders(ImportKind $kind, array $headers): void
    {
        $normalized = array_map(
            fn (string $header) => mb_strtolower(Str::ascii(trim($header))),
            $headers,
        );

        if (count(array_unique($normalized)) !== count($normalized)) {
            throw ValidationException::withMessages(['file' => 'O CSV possui cabecalhos equivalentes ou duplicados.']);
        }

        $required = $kind === ImportKind::Financial
            ? ['data', 'tipo', 'descricao', 'conta', 'conta destino', 'categoria', 'valor', 'moeda']
            : ['data', 'tipo', 'ativo', 'mercado', 'corretora', 'quantidade', 'preco unitario', 'taxas', 'valor bruto', 'valor liquido', 'proporcao origem', 'proporcao destino', 'observacao'];
        $missing = array_diff($required, $normalized);

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'file' => 'Cabecalhos obrigatorios ausentes: '.implode(', ', $missing).'.',
            ]);
        }
    }
}
