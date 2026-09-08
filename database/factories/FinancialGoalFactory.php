<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Models\User;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FinancialGoal> */
class FinancialGoalFactory extends Factory
{
    protected $model = FinancialGoal::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Reserva de emergencia', 'Viagem dos sonhos', 'Entrada do apartamento', 'Carro novo']),
            'description' => null,
            'target_amount' => fake()->randomFloat(2, 1000, 50000),
            'currency' => Currency::BRL,
            'account_id' => fn (array $attributes) => FinancialAccount::factory()->create([
                'user_id' => $attributes['user_id'],
            ])->id,
            'target_date' => now()->addYear()->toDateString(),
        ];
    }
}
