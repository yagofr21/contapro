<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Support\InstallmentMath;
use Carbon\CarbonInterface;
use Database\Factories\InstallmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $account_id
 * @property int|null $category_id
 * @property TransactionType $type
 * @property numeric-string $amount
 * @property numeric-string|null $total_amount
 * @property int $total_count
 * @property int $remaining_count
 * @property Carbon $next_due_date
 * @property string|null $description
 */
#[Fillable(['user_id', 'account_id', 'category_id', 'type', 'amount', 'total_amount', 'total_count', 'remaining_count', 'next_due_date', 'description'])]
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

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'installment_id');
    }

    /** Original purchase total (amount * total_count for legacy rows). */
    public function totalAmountValue(): string
    {
        return $this->total_amount ?? bcadd(bcmul($this->amount, (string) $this->total_count, 4), '0', 4);
    }

    public function paidCount(): int
    {
        return $this->total_count - $this->remaining_count;
    }

    public function isFinished(): bool
    {
        return $this->remaining_count <= 0;
    }

    public function currentParcela(): ?int
    {
        return $this->isFinished() ? null : ($this->paidCount() + 1);
    }

    public function amountForOrdinal(int $ordinal): string
    {
        $totalCents = InstallmentMath::amountToCents($this->totalAmountValue());

        return InstallmentMath::centsToAmount(
            InstallmentMath::amountsPerOrdinal($totalCents, $this->total_count)[$ordinal],
        );
    }

    /** @return list<array{number: int, due_date: CarbonInterface, amount: string, status: 'pago'|'pendente'}> */
    public function schedule(): array
    {
        return array_map(
            fn (array $row): array => [
                'number' => $row['number'],
                'due_date' => $row['due_date'],
                'amount' => $row['amount'],
                'status' => $row['status'],
            ],
            InstallmentMath::schedule($this),
        );
    }

    public function totalPaidValue(): string
    {
        return InstallmentMath::centsToAmount($this->paidCents());
    }

    private function paidCents(): int
    {
        $totalCents = InstallmentMath::amountToCents($this->totalAmountValue());
        $amounts = InstallmentMath::amountsPerOrdinal($totalCents, $this->total_count);
        $sum = 0;

        foreach (range(1, $this->paidCount()) as $ordinal) {
            $sum += $amounts[$ordinal];
        }

        return $sum;
    }

    public function totalRemainingValue(): string
    {
        $total = InstallmentMath::amountToCents($this->totalAmountValue());

        return InstallmentMath::centsToAmount($total - $this->paidCents());
    }

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'total_count' => 'integer',
            'remaining_count' => 'integer',
            'next_due_date' => 'date',
        ];
    }
}
