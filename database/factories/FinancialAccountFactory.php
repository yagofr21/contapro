<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Models\User;
use App\Modules\Finance\Enums\FinancialAccountType;
use App\Modules\Finance\Models\FinancialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FinancialAccount> */
class FinancialAccountFactory extends Factory
{
    protected $model = FinancialAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Conta corrente', 'Poupanca', 'Carteira']),
            'type' => FinancialAccountType::Checking,
            'currency' => Currency::BRL,
            'initial_balance' => fake()->randomFloat(2, 0, 10000),
            'is_archived' => false,
        ];
    }
}
