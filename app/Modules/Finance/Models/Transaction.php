<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $account_id
 * @property int|null $category_id
 * @property string|null $transfer_id
 * @property TransactionType $type
 * @property numeric-string $amount
 * @property Carbon $transaction_date
 * @property string|null $description
 * @property Transaction|null $transferCounterpart
 */
#[Fillable(['user_id', 'account_id', 'category_id', 'transfer_id', 'type', 'amount', 'transaction_date', 'description'])]
#[UseFactory(TransactionFactory::class)]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

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

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    /** @return HasOne<Transaction, $this> */
    public function transferCounterpart(): HasOne
    {
        return $this->hasOne(self::class, 'transfer_id', 'transfer_id')
            ->where('type', TransactionType::TransferIn->value);
    }

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:4',
            'transaction_date' => 'date',
        ];
    }
}
