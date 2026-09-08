<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Finance\Models\ExpectedIncome;
use App\Modules\Finance\Models\FinancialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExpectedIncome> */
class ExpectedIncomeFactory extends Factory
{
    protected $model = ExpectedIncome::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'description' => fake()->sentence(3),
            'amount' => fake()->randomFloat(2, 50, 5000),
            'currency' => 'BRL',
            'account_id' => fn (array $attributes) => FinancialAccount::factory()->create([
                'user_id' => $attributes['user_id'],
            ])->id,
            'category_id' => null,
            'expected_date' => fake()->dateTimeBetween('-1 month', '+3 months'),
            'received_at' => null,
            'transaction_id' => null,
        ];
    }
}
