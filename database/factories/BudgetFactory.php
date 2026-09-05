<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Finance\Enums\BudgetPeriod;
use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Budget> */
class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => fn (array $attributes) => Category::factory()->create([
                'user_id' => $attributes['user_id'],
            ])->id,
            'limit_amount' => fake()->randomFloat(2, 100, 3000),
            'period' => BudgetPeriod::Monthly,
            'starts_on' => now()->startOfMonth(),
            'ends_on' => null,
        ];
    }
}
