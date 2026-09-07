<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use Database\Factories\InstallmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $account_id
 * @property int|null $category_id
 * @property TransactionType $type
 * @property numeric-string $amount
 * @property int $total_count
 * @property int $remaining_count
 * @property Carbon $next_due_date
 * @property string|null $description
 */
#[Fillable(['user_id', 'account_id', 'category_id', 'type', 'amount', 'total_count', 'remaining_count', 'next_due_date', 'description'])]
#[UseFactory(InstallmentFactory::class)]
class Installment extends Model
{
    /** @use HasFactory<InstallmentFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<FinancialAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'account_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function isFinished(): bool
    {
        return $this->remaining_count <= 0;
    }

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:4',
            'total_count' => 'integer',
            'remaining_count' => 'integer',
            'next_due_date' => 'date',
        ];
    }
}
