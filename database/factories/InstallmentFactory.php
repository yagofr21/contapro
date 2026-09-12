<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Installment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Installment> */
class InstallmentFactory extends Factory
{
    protected $model = Installment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_id' => fn (array $attributes) => FinancialAccount::factory()->create([
                'user_id' => $attributes['user_id'],
            ])->id,
            'category_id' => null,
            'type' => TransactionType::Expense,
            'amount' => fake()->randomFloat(2, 10, 500),
            'total_amount' => fn (array $attributes) => number_format(
                (float) $attributes['amount'] * (int) $attributes['total_count'],
                4,
                '.',
                '',
            ),
            'total_count' => 10,
            'remaining_count' => 10,
            'next_due_date' => now()->startOfMonth(),
            'description' => fake()->sentence(3),
        ];
    }
}
