<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'parent_id' => null,
            'name' => fake()->randomElement(['Moradia', 'Alimentacao', 'Salario', 'Transporte']),
            'type' => CategoryType::Expense,
            'color' => fake()->hexColor(),
        ];
    }
}
