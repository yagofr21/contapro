<?php

namespace App\Modules\Investment\Models;

use App\Enums\Currency;
use App\Models\User;
use Database\Factories\PortfolioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property Currency $currency
 */
#[Fillable(['user_id', 'name', 'currency'])]
#[UseFactory(PortfolioFactory::class)]
class Portfolio extends Model
{
    /** @use HasFactory<PortfolioFactory> */
    use HasFactory, SoftDeletes;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<PortfolioHolding, $this> */
    public function holdings(): HasMany
    {
        return $this->hasMany(PortfolioHolding::class);
    }

    /** @return HasMany<AssetTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(AssetTransaction::class);
    }

    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
        ];
    }
}
