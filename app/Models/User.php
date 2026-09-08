<?php

namespace App\Models;

use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialGoal;
use App\Modules\Finance\Models\Installment;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Models\TransactionSchedule;
use App\Modules\ImportExport\Models\ImportBatch;
use App\Modules\ImportExport\Models\Reconciliation;
use App\Modules\Investment\Models\Broker;
use App\Modules\Investment\Models\Portfolio;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'locale', 'timezone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @return HasMany<FinancialAccount, $this> */
    public function financialAccounts(): HasMany
    {
        return $this->hasMany(FinancialAccount::class);
    }

    /** @return HasMany<Category, $this> */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** @return HasMany<Budget, $this> */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /** @return HasMany<Portfolio, $this> */
    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }

    /** @return HasMany<Broker, $this> */
    public function brokers(): HasMany
    {
        return $this->hasMany(Broker::class);
    }

    /** @return HasMany<ImportBatch, $this> */
    public function importBatches(): HasMany
    {
        return $this->hasMany(ImportBatch::class);
    }

    /** @return HasMany<Reconciliation, $this> */
    public function reconciliations(): HasMany
    {
        return $this->hasMany(Reconciliation::class);
    }

    /** @return HasMany<TransactionSchedule, $this> */
    public function transactionSchedules(): HasMany
    {
        return $this->hasMany(TransactionSchedule::class);
    }

    /** @return HasMany<Installment, $this> */
    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    /** @return HasMany<FinancialGoal, $this> */
    public function financialGoals(): HasMany
    {
        return $this->hasMany(FinancialGoal::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
