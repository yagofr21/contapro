<?php

namespace App\Modules\ImportExport\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $import_batch_id
 * @property int $row_number
 * @property array<string, string> $raw
 * @property array<string, mixed>|null $normalized
 * @property string|null $fingerprint
 * @property string $status
 * @property list<string>|null $errors
 * @property string|null $importable_type
 * @property int|null $importable_id
 */
#[Fillable(['import_batch_id', 'row_number', 'raw', 'normalized', 'fingerprint', 'status', 'errors', 'importable_type', 'importable_id'])]
class ImportRow extends Model
{
    /** @return BelongsTo<ImportBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    protected function casts(): array
    {
        return [
            'raw' => 'array',
            'normalized' => 'array',
            'errors' => 'array',
        ];
    }
}
