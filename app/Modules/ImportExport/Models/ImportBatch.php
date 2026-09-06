<?php

namespace App\Modules\ImportExport\Models;

use App\Models\User;
use App\Modules\ImportExport\Enums\ImportKind;
use App\Modules\ImportExport\Enums\ImportStatus;
use App\Modules\Investment\Models\Portfolio;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int $user_id
 * @property int|null $portfolio_id
 * @property ImportKind $kind
 * @property ImportStatus $status
 * @property string $original_filename
 * @property string $file_sha256
 * @property array<string, int|string> $summary
 * @property Carbon|null $confirmed_at
 * @property Carbon $created_at
 * @property Portfolio|null $portfolio
 */
#[Fillable(['public_id', 'user_id', 'portfolio_id', 'kind', 'status', 'original_filename', 'file_sha256', 'summary', 'confirmed_at'])]
class ImportBatch extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Portfolio, $this> */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    /** @return HasMany<ImportRow, $this> */
    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected function casts(): array
    {
        return [
            'kind' => ImportKind::class,
            'status' => ImportStatus::class,
            'summary' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }
}
