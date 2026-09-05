<?php

namespace App\Modules\Investment\Models;

use App\Modules\Investment\Enums\AssetTransactionType;
use Database\Factories\AssetTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $portfolio_id
 * @property int $asset_id
 * @property AssetTransactionType $type
 * @property numeric-string $quantity
 * @property numeric-string $unit_price
 * @property numeric-string $fees
 * @property numeric-string|null $gross_amount
 * @property numeric-string|null $net_amount
 * @property Carbon $transaction_date
 * @property string|null $note
 */
#[Fillable(['portfolio_id', 'asset_id', 'type', 'quantity', 'unit_price', 'fees', 'gross_amount', 'net_amount', 'transaction_date', 'note'])]
#[UseFactory(AssetTransactionFactory::class)]
class AssetTransaction extends Model
{
    /** @use HasFactory<AssetTransactionFactory> */
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
            'type' => AssetTransactionType::class,
            'quantity' => 'decimal:8',
            'unit_price' => 'decimal:8',
            'fees' => 'decimal:4',
            'gross_amount' => 'decimal:4',
            'net_amount' => 'decimal:4',
            'transaction_date' => 'date',
        ];
    }
}
