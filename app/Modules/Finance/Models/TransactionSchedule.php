<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use App\Modules\Finance\Enums\ScheduleFrequency;
use App\Modules\Finance\Enums\TransactionType;
use Database\Factories\TransactionScheduleFactory;
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
 * @property int|null $destination_account_id
 * @property int|null $category_id
 * @property TransactionType $type
 * @property numeric-string $amount
 * @property ScheduleFrequency $frequency
 * @property Carbon $starts_on
 * @property Carbon|null $ends_on
 * @property Carbon $next_run_date
 * @property string|null $description
 * @property bool $is_active
 */
#[Fillable(['user_id', 'account_id', 'destination_account_id', 'category_id', 'type', 'amount', 'frequency', 'starts_on', 'ends_on', 'next_run_date', 'description', 'is_active'])]
#[UseFactory(TransactionScheduleFactory::class)]
class TransactionSchedule extends Model
{
    /** @use HasFactory<TransactionScheduleFactory> */
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

    /** @return BelongsTo<FinancialAccount, $this> */
    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'destination_account_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'frequency' => ScheduleFrequency::class,
            'amount' => 'decimal:4',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'next_run_date' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
