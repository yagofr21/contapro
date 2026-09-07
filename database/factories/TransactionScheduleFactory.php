<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Finance\Enums\ScheduleFrequency;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\TransactionSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TransactionSchedule> */
class TransactionScheduleFactory extends Factory
{
    protected $model = TransactionSchedule::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_id' => fn (array $attributes) => FinancialAccount::factory()->create([
                'user_id' => $attributes['user_id'],
            ])->id,
            'destination_account_id' => null,
            'category_id' => null,
            'type' => TransactionType::Expense,
            'amount' => fake()->randomFloat(2, 10, 500),
            'frequency' => ScheduleFrequency::Monthly,
            'starts_on' => now()->startOfMonth(),
            'ends_on' => null,
            'next_run_date' => now()->startOfMonth(),
            'description' => fake()->sentence(3),
            'is_active' => true,
        ];
    }
}
