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
            'bank' => null,
            'color' => null,
            'type' => FinancialAccountType::Checking,
            'currency' => Currency::BRL,
            'initial_balance' => fake()->randomFloat(2, 0, 10000),
            'credit_limit' => null,
            'credit_closing_day' => null,
            'credit_due_day' => null,
            'is_archived' => false,
        ];
    }

    public function creditCard(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Cartao de credito',
            'type' => FinancialAccountType::CreditCard,
            'initial_balance' => '0.0000',
            'credit_limit' => '5000.0000',
            'credit_closing_day' => 15,
            'credit_due_day' => 22,
        ]);
    }
}
