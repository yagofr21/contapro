<?php

namespace App\Modules\Finance\Models;

use App\Enums\Currency;
use App\Models\User;
use Database\Factories\ExpectedIncomeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $description
 * @property numeric-string $amount
 * @property Currency $currency
 * @property int $account_id
 * @property int|null $category_id
 * @property Carbon $expected_date
 * @property Carbon|null $received_at
 * @property int|null $transaction_id
 * @property-read bool $received
 */
#[Fillable(['description', 'amount', 'currency', 'account_id', 'category_id', 'expected_date', 'received_at', 'transaction_id'])]
#[UseFactory(ExpectedIncomeFactory::class)]
class ExpectedIncome extends Model
{
    /** @use HasFactory<ExpectedIncomeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'currency' => Currency::class,
            'expected_date' => 'date',
            'received_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<FinancialAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    /** @return BelongsTo<Transaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function getReceivedAttribute(): bool
    {
        return $this->received_at !== null;
    }
}
