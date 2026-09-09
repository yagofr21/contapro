<?php

namespace App\Modules\Investment\Models;

use App\Enums\Currency;
use App\Modules\Investment\Enums\AssetType;
use App\Modules\Investment\Enums\Market;
use App\Modules\MarketData\Models\PriceHistory;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $symbol
 * @property string $name
 * @property AssetType $type
 * @property Market $market
 * @property Currency $currency
 * @property bool $is_active
 * @property-read bool $price_history_exists
 */
#[Fillable(['symbol', 'name', 'type', 'market', 'currency', 'is_active'])]
#[UseFactory(AssetFactory::class)]
class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory, SoftDeletes;

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

    /** @return HasMany<PriceHistory, $this> */
    public function priceHistory(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    /** @return HasMany<AssetPreference, $this> */
    public function assetPreferences(): HasMany
    {
        return $this->hasMany(AssetPreference::class);
    }

    /** @return HasOne<PriceHistory, $this> */
    public function latestPrice(): HasOne
    {
        return $this->hasOne(PriceHistory::class)->latestOfMany('price_date');
    }

    protected function casts(): array
    {
        return [
            'type' => AssetType::class,
            'market' => Market::class,
            'currency' => Currency::class,
            'is_active' => 'boolean',
        ];
    }
}
