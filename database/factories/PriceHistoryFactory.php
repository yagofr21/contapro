<?php

namespace Database\Factories;

use App\Modules\Investment\Models\Asset;
use App\Modules\MarketData\Models\PriceHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PriceHistory> */
class PriceHistoryFactory extends Factory
{
    protected $model = PriceHistory::class;

    public function definition(): array
    {
        $close = fake()->randomFloat(4, 5, 500);

        return [
            'asset_id' => Asset::factory(),
            'price_date' => fake()->unique()->dateTimeBetween('-1 year', 'now'),
            'open' => $close,
            'high' => $close,
            'low' => $close,
            'close' => $close,
            'adjusted_close' => $close,
            'volume' => fake()->numberBetween(1000, 10000000),
        ];
    }
}
