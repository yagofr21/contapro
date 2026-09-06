<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Finance\Enums\BudgetPeriod;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_budget_for_their_expense_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create([
            'type' => CategoryType::Expense,
        ]);

        $this->actingAs($user)->post(route('budgets.store'), [
            'category_id' => $category->id,
            'limit_amount' => '1.500,25',
            'period' => BudgetPeriod::Monthly->value,
            'starts_on' => '2026-09-01',
        ])->assertRedirect(route('budgets.index'));

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'limit_amount' => '1500.2500',
        ]);
    }

    public function test_budget_only_accepts_owned_expense_categories(): void
    {
        $user = User::factory()->create();
        $incomeCategory = Category::factory()->for($user)->create([
            'type' => CategoryType::Income,
        ]);
        $otherCategory = Category::factory()->create([
            'type' => CategoryType::Expense,
        ]);

        foreach ([$incomeCategory, $otherCategory] as $category) {
            $this->actingAs($user)->post(route('budgets.store'), [
                'category_id' => $category->id,
                'limit_amount' => '100.0000',
                'period' => BudgetPeriod::Monthly->value,
                'starts_on' => '2026-09-01',
            ])->assertSessionHasErrors('category_id');
        }
    }

    public function test_budget_index_calculates_spent_amount_in_period(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create([
            'type' => CategoryType::Expense,
        ]);
        $account = FinancialAccount::factory()->for($user)->create();
        Budget::factory()->for($user)->for($category)->create([
            'period' => BudgetPeriod::Monthly,
            'starts_on' => '2026-09-01',
            'limit_amount' => '500.0000',
        ]);
        Transaction::factory()->for($user)->for($account, 'account')->for($category)->create([
            'type' => TransactionType::Expense,
            'transaction_date' => '2026-09-10',
            'amount' => '125.5000',
        ]);

        $this->actingAs($user)
            ->get(route('budgets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Budgets/Index')
                ->has('budgets', 1)
                ->where('budgets.0.spent', '125.5000'));
    }

    public function test_user_cannot_update_another_users_budget(): void
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create();

        $this->actingAs($user)->put(route('budgets.update', $budget), [
            'category_id' => $budget->category_id,
            'limit_amount' => '200.0000',
            'period' => BudgetPeriod::Monthly->value,
            'starts_on' => '2026-09-01',
        ])->assertForbidden();
    }
}
