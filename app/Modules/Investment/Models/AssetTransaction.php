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
 * @property int|null $broker_id
 * @property AssetTransactionType $type
 * @property numeric-string $quantity
 * @property numeric-string $unit_price
 * @property numeric-string $fees
 * @property numeric-string|null $gross_amount
 * @property numeric-string|null $net_amount
 * @property numeric-string|null $split_from
 * @property numeric-string|null $split_to
 * @property numeric-string|null $realized_cost_basis
 * @property numeric-string|null $realized_profit_loss
 * @property Carbon $transaction_date
 * @property string|null $note
 */
#[Fillable(['portfolio_id', 'asset_id', 'broker_id', 'type', 'quantity', 'unit_price', 'fees', 'split_from', 'split_to', 'gross_amount', 'net_amount', 'realized_cost_basis', 'realized_profit_loss', 'transaction_date', 'note'])]
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

    /** @return BelongsTo<Broker, $this> */
    public function broker(): BelongsTo
    {
        return $this->belongsTo(Broker::class);
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
            'split_from' => 'decimal:8',
            'split_to' => 'decimal:8',
            'realized_cost_basis' => 'decimal:4',
            'realized_profit_loss' => 'decimal:4',
            'transaction_date' => 'date',
        ];
    }
}
