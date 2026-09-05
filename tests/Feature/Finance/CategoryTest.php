<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_nested_category_of_the_same_type(): void
    {
        $user = User::factory()->create();
        $parent = Category::factory()->for($user)->create([
            'type' => CategoryType::Expense,
        ]);

        $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Supermercado',
            'type' => CategoryType::Expense->value,
            'parent_id' => $parent->id,
            'color' => '#338dff',
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'parent_id' => $parent->id,
            'name' => 'Supermercado',
        ]);
    }

    public function test_parent_category_must_belong_to_user_and_have_same_type(): void
    {
        $user = User::factory()->create();
        $otherParent = Category::factory()->create();
        $incomeParent = Category::factory()->for($user)->create([
            'type' => CategoryType::Income,
        ]);

        $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Invalida',
            'type' => CategoryType::Expense->value,
            'parent_id' => $otherParent->id,
            'color' => '#338dff',
        ])->assertSessionHasErrors('parent_id');

        $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Tipo invalido',
            'type' => CategoryType::Expense->value,
            'parent_id' => $incomeParent->id,
            'color' => '#338dff',
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_user_cannot_delete_another_users_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->delete(route('categories.destroy', $category))
            ->assertForbidden();

        $this->assertNotSoftDeleted($category);
    }
}
