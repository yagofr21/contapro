<?php

namespace App\Modules\Finance\Models;

use App\Enums\Currency;
use App\Models\User;
use App\Modules\Finance\Enums\FinancialAccountType;
use Database\Factories\FinancialAccountFactory;
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
 * @property string|null $bank
 * @property string|null $color
 * @property FinancialAccountType $type
 * @property Currency $currency
 * @property numeric-string $initial_balance
 * @property numeric-string|null $credit_limit
 * @property int|null $credit_closing_day
 * @property int|null $credit_due_day
 * @property numeric-string|null $credits
 * @property numeric-string|null $debits
 * @property bool $is_archived
 */
#[Fillable(['user_id', 'name', 'bank', 'color', 'type', 'currency', 'initial_balance', 'credit_limit', 'credit_closing_day', 'credit_due_day', 'is_archived'])]
#[UseFactory(FinancialAccountFactory::class)]
class FinancialAccount extends Model
{
    /** @use HasFactory<FinancialAccountFactory> */
    use HasFactory, SoftDeletes;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'account_id');
    }

    /** @return HasMany<Installment, $this> */
    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class, 'account_id');
    }

    protected function casts(): array
    {
        return [
            'type' => FinancialAccountType::class,
            'currency' => Currency::class,
            'initial_balance' => 'decimal:4',
            'credit_limit' => 'decimal:4',
            'credit_closing_day' => 'integer',
            'credit_due_day' => 'integer',
            'is_archived' => 'boolean',
        ];
    }
}
