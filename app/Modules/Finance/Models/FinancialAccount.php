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
 * @property FinancialAccountType $type
 * @property Currency $currency
 * @property numeric-string $initial_balance
 * @property numeric-string|null $credits
 * @property numeric-string|null $debits
 * @property bool $is_archived
 */
#[Fillable(['user_id', 'name', 'type', 'currency', 'initial_balance', 'is_archived'])]
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

    protected function casts(): array
    {
        return [
            'type' => FinancialAccountType::class,
            'currency' => Currency::class,
            'initial_balance' => 'decimal:4',
            'is_archived' => 'boolean',
        ];
    }
}
