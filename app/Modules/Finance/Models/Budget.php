<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use App\Modules\Finance\Enums\BudgetPeriod;
use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $category_id
 * @property numeric-string $limit_amount
 * @property BudgetPeriod $period
 * @property Carbon $starts_on
 * @property Carbon|null $ends_on
 */
#[Fillable(['user_id', 'category_id', 'limit_amount', 'period', 'starts_on', 'ends_on'])]
#[UseFactory(BudgetFactory::class)]
class Budget extends Model
{
    /** @use HasFactory<BudgetFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    protected function casts(): array
    {
        return [
            'limit_amount' => 'decimal:4',
            'period' => BudgetPeriod::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }
}
