<?php

namespace Database\Factories;

use App\Modules\Investment\Enums\AssetTransactionType;
use App\Modules\Investment\Models\Asset;
use App\Modules\Investment\Models\AssetTransaction;
use App\Modules\Investment\Models\Portfolio;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AssetTransaction> */
class AssetTransactionFactory extends Factory
{
    protected $model = AssetTransaction::class;

    public function definition(): array
    {
        return [
            'portfolio_id' => Portfolio::factory(),
            'asset_id' => Asset::factory(),
            'type' => AssetTransactionType::Buy,
            'quantity' => fake()->randomFloat(4, 1, 100),
            'unit_price' => fake()->randomFloat(4, 1, 500),
            'fees' => fake()->randomFloat(2, 0, 20),
            'transaction_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'note' => null,
        ];
    }

    public function dividend(): static
    {
        return $this->state(fn (): array => [
            'type' => AssetTransactionType::Dividend,
            'quantity' => '0',
            'unit_price' => '0',
            'fees' => '0',
            'gross_amount' => '125.5000',
            'net_amount' => '106.6750',
        ]);
    }

    public function interest(): static
    {
        return $this->state(fn (): array => [
            'type' => AssetTransactionType::Interest,
            'quantity' => '0',
            'unit_price' => '0',
            'fees' => '0',
            'gross_amount' => '80.0000',
            'net_amount' => '80.0000',
        ]);
    }
}
