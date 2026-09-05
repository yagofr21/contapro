<?php

namespace Database\Factories;

use App\Modules\Investment\Models\Asset;
use App\Modules\Investment\Models\Portfolio;
use App\Modules\Investment\Models\PortfolioHolding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PortfolioHolding> */
class PortfolioHoldingFactory extends Factory
{
    protected $model = PortfolioHolding::class;

    public function definition(): array
    {
        return [
            'portfolio_id' => Portfolio::factory(),
            'asset_id' => Asset::factory(),
            'quantity' => fake()->randomFloat(4, 1, 1000),
            'average_cost' => fake()->randomFloat(4, 1, 500),
        ];
    }
}
