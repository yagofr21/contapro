<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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

    public function test_show_renders_monthly_calendar_with_daily_totals_and_summaries(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create([
            'type' => CategoryType::Expense,
        ]);
        $account = FinancialAccount::factory()->for($user)->create(['currency' => 'BRL']);

        Transaction::factory()->for($user)->for($account, 'account')->for($category)->create([
            'amount' => '-100.0000',
            'transaction_date' => '2025-01-05',
        ]);
        Transaction::factory()->for($user)->for($account, 'account')->for($category)->create([
            'amount' => '-200.0000',
            'transaction_date' => '2025-01-15',
        ]);

        $response = $this->actingAs($user)
            ->get(route('categories.show', $category).'?month=2025-01')
            ->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Categories/Show')
            ->where('month', '2025-01')
            ->where('heatCurrency', 'BRL')
            ->has('calendar', 31)
            ->where('calendar.4.date', '2025-01-05')
            ->where('calendar.4.day', 5)
            ->where('summaries.BRL.days', 2)
            ->where('summaries.BRL.total', '-300.0000')
            ->where('summaries.BRL.avg', '-150.0000')
            ->where('summaries.BRL.top.date', '2025-01-05')
            ->where('summaries.BRL.low.date', '2025-01-15'));

        $props = $response->viewData('page')['props'];

        $this->assertSame([], $props['calendar'][0]['totals']);
        $this->assertTrue(bccomp($props['calendar'][4]['totals']['BRL'], '-100', 4) === 0);
        $this->assertTrue(bccomp($props['calendar'][14]['totals']['BRL'], '-200', 4) === 0);
    }

    public function test_show_filters_transactions_by_requested_month(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create([
            'type' => CategoryType::Expense,
        ]);
        $account = FinancialAccount::factory()->for($user)->create(['currency' => 'BRL']);

        Transaction::factory()->for($user)->for($account, 'account')->for($category)->create([
            'amount' => '-50.0000',
            'transaction_date' => '2025-06-10',
        ]);

        $this->actingAs($user)
            ->get(route('categories.show', $category).'?month=2025-01')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Categories/Show')
                ->has('calendar', 31)
                ->where('summaries', []));
    }

    public function test_show_includes_subcategory_transactions(): void
    {
        $user = User::factory()->create();
        $parent = Category::factory()->for($user)->create([
            'type' => CategoryType::Expense,
        ]);
        $child = Category::factory()->for($user)->create([
            'type' => CategoryType::Expense,
            'parent_id' => $parent->id,
        ]);
        $account = FinancialAccount::factory()->for($user)->create(['currency' => 'BRL']);

        Transaction::factory()->for($user)->for($account, 'account')->for($child)->create([
            'amount' => '-75.0000',
            'transaction_date' => '2025-02-11',
        ]);

        $this->actingAs($user)
            ->get(route('categories.show', $parent).'?month=2025-02')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Categories/Show')
                ->where('calendar.10.date', '2025-02-11')
                ->where('summaries.BRL.days', 1)
                ->where('summaries.BRL.total', '-75.0000'));
    }

    public function test_show_ignores_other_users_transactions(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->for($user)->create([
            'type' => CategoryType::Expense,
        ]);
        $otherCategory = Category::factory()->for($other)->create([
            'type' => CategoryType::Expense,
        ]);
        $otherAccount = FinancialAccount::factory()->for($other)->create(['currency' => 'BRL']);

        Transaction::factory()->for($other)->for($otherAccount, 'account')->for($otherCategory)->create([
            'amount' => '-99.0000',
            'transaction_date' => '2025-03-03',
        ]);

        $this->actingAs($user)
            ->get(route('categories.show', $category).'?month=2025-03')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Categories/Show')
                ->where('summaries', []));
    }

    public function test_show_is_forbidden_for_another_users_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->get(route('categories.show', $category))
            ->assertForbidden();
    }
}
