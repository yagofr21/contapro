<?php

namespace App\Modules\Investment\Models;

use Database\Factories\PortfolioHoldingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $portfolio_id
 * @property int $asset_id
 * @property numeric-string $quantity
 * @property numeric-string $average_cost
 */
#[Fillable(['portfolio_id', 'asset_id', 'quantity', 'average_cost'])]
#[UseFactory(PortfolioHoldingFactory::class)]
class PortfolioHolding extends Model
{
    /** @use HasFactory<PortfolioHoldingFactory> */
    use HasFactory;

    /** @return BelongsTo<Portfolio, $this> */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    /** @return BelongsTo<Asset, $this> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:8',
            'average_cost' => 'decimal:8',
        ];
    }
}
