<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Models\User;
use App\Modules\Investment\Models\Portfolio;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Portfolio> */
class PortfolioFactory extends Factory
{
    protected $model = Portfolio::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Principal', 'Aposentadoria', 'Exterior']),
            'currency' => Currency::BRL,
        ];
    }
}
