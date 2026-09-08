<?php

namespace App\Modules\Finance\Models;

use App\Enums\Currency;
use App\Models\User;
use Database\Factories\FinancialGoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property numeric-string $target_amount
 * @property Currency $currency
 * @property int|null $account_id
 * @property Carbon $target_date
 */
#[Fillable(['user_id', 'name', 'description', 'target_amount', 'currency', 'account_id', 'target_date'])]
#[UseFactory(FinancialGoalFactory::class)]
class FinancialGoal extends Model
{
    /** @use HasFactory<FinancialGoalFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<FinancialAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class)->withTrashed();
    }

    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
            'target_amount' => 'decimal:4',
            'target_date' => 'date',
        ];
    }
}
