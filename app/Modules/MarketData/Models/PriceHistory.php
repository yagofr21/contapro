<?php

namespace App\Modules\MarketData\Models;

use App\Modules\Investment\Models\Asset;
use Database\Factories\PriceHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $asset_id
 * @property Carbon $price_date
 * @property numeric-string $close
 * @property numeric-string|null $adjusted_close
 */
#[Fillable(['asset_id', 'price_date', 'open', 'high', 'low', 'close', 'adjusted_close', 'volume'])]
#[UseFactory(PriceHistoryFactory::class)]
class PriceHistory extends Model
{
    /** @use HasFactory<PriceHistoryFactory> */
    use HasFactory;

    protected $table = 'price_history';

    /** @return BelongsTo<Asset, $this> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    protected function casts(): array
    {
        return [
            'price_date' => 'date',
            'open' => 'decimal:8',
            'high' => 'decimal:8',
            'low' => 'decimal:8',
            'close' => 'decimal:8',
            'adjusted_close' => 'decimal:8',
            'volume' => 'integer',
        ];
    }
}
