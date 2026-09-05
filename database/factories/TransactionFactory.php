<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Transaction> */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_id' => fn (array $attributes) => FinancialAccount::factory()->create([
                'user_id' => $attributes['user_id'],
            ])->id,
            'category_id' => null,
            'transfer_id' => null,
            'type' => TransactionType::Expense,
            'amount' => fake()->randomFloat(2, 10, 500),
            'transaction_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'description' => fake()->sentence(3),
        ];
    }
}
