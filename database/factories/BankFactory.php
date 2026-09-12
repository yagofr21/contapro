<?php

namespace Database\Factories;

use App\Modules\Finance\Models\Bank;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Bank> */
class BankFactory extends Factory
{
    protected $model = Bank::class;

    public function definition(): array
    {
        return [
            'code' => strtolower((string) fake()->unique()->word()),
            'label' => fake()->company(),
            'color' => fake()->hexColor(),
            'initials' => strtoupper((string) fake()->unique()->bothify('??')),
            'is_active' => true,
        ];
    }
}
