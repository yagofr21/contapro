<?php

namespace App\Modules\Finance\Queries;

use App\Models\User;
use App\Modules\Finance\Models\ExpectedIncome;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpectedIncomeQuery
{
    /**
     * @return list<array<string, mixed>>
     */
    public function pendingFor(User $user, int $limit = 100): array
    {
        return $this->rows(
            $user->expectedIncomes()
                ->with(['account:id,name,currency', 'category:id,name,color'])
                ->whereNull('received_at')
                ->orderBy('expected_date')
                ->orderBy('id')
                ->limit($limit),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentFor(User $user, int $limit = 20): array
    {
        return $this->rows(
            $user->expectedIncomes()
                ->with(['account:id,name,currency', 'category:id,name,color'])
                ->whereNotNull('received_at')
                ->orderByDesc('received_at')
                ->limit($limit),
        );
    }

    /**
     * @param  Builder<ExpectedIncome>|HasMany<ExpectedIncome, User>  $query
     * @return list<array<string, mixed>>
     */
    private function rows($query): array
    {
        return $query->get()->map(fn (ExpectedIncome $income): array => [
            'id' => $income->id,
            'description' => $income->description,
            'amount' => $income->amount,
            'currency' => $income->currency->value,
            'account_id' => $income->account_id,
            'account_name' => $income->account?->name,
            'category_id' => $income->category_id,
            'category_name' => $income->category?->name,
            'category_color' => $income->category?->color,
            'expected_date' => $income->expected_date->format('Y-m-d'),
            'received' => $income->received,
            'received_at' => $income->received_at?->toDateString(),
            'transaction_id' => $income->transaction_id,
        ])->all();
    }
}
