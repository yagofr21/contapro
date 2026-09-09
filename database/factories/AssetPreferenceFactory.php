<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Investment\Models\Asset;
use App\Modules\Investment\Models\AssetPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AssetPreference> */
class AssetPreferenceFactory extends Factory
{
    protected $model = AssetPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'asset_id' => Asset::factory(),
            'auto_update' => true,
        ];
    }
}
