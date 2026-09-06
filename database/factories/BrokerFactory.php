<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Investment\Models\Broker;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Broker> */
class BrokerFactory extends Factory
{
    protected $model = Broker::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'is_active' => true,
        ];
    }
}
