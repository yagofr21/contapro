<?php

namespace App\Modules\ImportExport\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reconciliation_id
 * @property int $row_number
 * @property array<string, string> $raw
 * @property Carbon|null $entry_date
 * @property numeric-string $amount
 * @property string|null $description
 * @property string $status
 * @property string|null $match_rule
 * @property int|null $matched_transaction_id
 * @property list<string>|null $errors
 */
#[Fillable(['reconciliation_id', 'row_number', 'raw', 'entry_date', 'amount', 'description', 'status', 'match_rule', 'matched_transaction_id', 'errors'])]
class ReconciliationRow extends Model
{
    /** @return BelongsTo<Reconciliation, $this> */
    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class, 'reconciliation_id');
    }

    protected function casts(): array
    {
        return [
            'raw' => 'array',
            'entry_date' => 'date',
            'amount' => 'decimal:4',
            'errors' => 'array',
        ];
    }
}
