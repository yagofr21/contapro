<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Modules\Investment\Enums\AssetType;
use App\Modules\Investment\Enums\Market;
use App\Modules\Investment\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Asset> */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'symbol' => strtoupper(fake()->unique()->bothify('????#')),
            'name' => fake()->company(),
            'type' => AssetType::Stock,
            'market' => Market::B3,
            'currency' => Currency::BRL,
            'is_active' => true,
        ];
    }
}
