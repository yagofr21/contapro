<?php

namespace App\Modules\ImportExport\Models;

use App\Models\User;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\ImportExport\Enums\ReconciliationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int $user_id
 * @property int $account_id
 * @property string $kind
 * @property ReconciliationStatus $status
 * @property Carbon $period_start
 * @property Carbon $statement_date
 * @property numeric-string $declared_balance
 * @property numeric-string|null $detected_balance
 * @property numeric-string|null $delta
 * @property array<string, int> $summary
 * @property Carbon|null $confirmed_at
 * @property Carbon $created_at
 * @property FinancialAccount|null $account
 */
#[Fillable(['public_id', 'user_id', 'account_id', 'kind', 'status', 'period_start', 'statement_date', 'declared_balance', 'detected_balance', 'delta', 'summary', 'confirmed_at'])]
class Reconciliation extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<FinancialAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'account_id')->withTrashed();
    }

    /** @return HasMany<ReconciliationRow, $this> */
    public function rows(): HasMany
    {
        return $this->hasMany(ReconciliationRow::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected function casts(): array
    {
        return [
            'status' => ReconciliationStatus::class,
            'period_start' => 'date',
            'statement_date' => 'date',
            'declared_balance' => 'decimal:4',
            'detected_balance' => 'decimal:4',
            'delta' => 'decimal:4',
            'summary' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }
}
