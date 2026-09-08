<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\ExpectedIncome;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpectedIncomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_registers_an_expected_income(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create(['currency' => 'BRL']);
        $category = Category::factory()->for($user)->create(['type' => CategoryType::Income]);

        $this->actingAs($user)->post(route('expected-incomes.store'), [
            'description' => 'Salario',
            'amount' => '2.500,99',
            'currency' => 'BRL',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'expected_date' => now()->addDays(5)->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('expected_incomes', [
            'user_id' => $user->id,
            'description' => 'Salario',
            'amount' => '2500.9900',
            'currency' => 'BRL',
            'account_id' => $account->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_category_must_be_income_type_and_currency_must_match_the_account(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create(['currency' => 'BRL']);
        $expenseCategory = Category::factory()->for($user)->create(['type' => CategoryType::Expense]);

        $this->actingAs($user)->post(route('expected-incomes.store'), [
            'description' => 'Compras',
            'amount' => '90,00',
            'currency' => 'BRL',
            'account_id' => $account->id,
            'category_id' => $expenseCategory->id,
            'expected_date' => now()->toDateString(),
        ])->assertSessionHasErrors('category_id');

        $this->actingAs($user)->post(route('expected-incomes.store'), [
            'description' => 'Compras',
            'amount' => '90,00',
            'currency' => 'USD',
            'account_id' => $account->id,
            'expected_date' => now()->toDateString(),
        ])->assertSessionHasErrors('currency');

        $this->assertDatabaseCount('expected_incomes', 0);
    }

    public function test_receiving_creates_a_real_income_transaction_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create(['currency' => 'BRL']);
        $category = Category::factory()->for($user)->create(['type' => CategoryType::Income]);
        $income = ExpectedIncome::factory()->for($user)->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => '1200.0000',
            'description' => 'Emprestimo a amigo',
        ]);

        $this->actingAs($user)->post(route('expected-incomes.receive', $income))->assertRedirect();
        $this->actingAs($user)->post(route('expected-incomes.receive', $income))->assertRedirect();

        $this->assertSame(1, Transaction::where('user_id', $user->id)->count());
        $transaction = Transaction::where('user_id', $user->id)->sole();
        $this->assertSame(TransactionType::Income, $transaction->type);
        $this->assertSame('1200.0000', $transaction->amount);
        $this->assertSame('Emprestimo a amigo', $transaction->description);
        $this->assertSame($account->id, $transaction->account_id);
        $this->assertSame($category->id, $transaction->category_id);

        $fresh = $income->fresh();
        $this->assertNotNull($fresh->received_at);
        $this->assertSame($transaction->id, $fresh->transaction_id);
    }

    public function test_user_cannot_receive_or_delete_another_users_expected_income(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $income = ExpectedIncome::factory()->for($user)->create([
            'account_id' => FinancialAccount::factory()->for($user)->create()->id,
        ]);

        $this->actingAs($other)->post(route('expected-incomes.receive', $income))->assertForbidden();
        $this->actingAs($other)->delete(route('expected-incomes.destroy', $income))->assertForbidden();

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('expected_incomes', 1);
    }

    public function test_received_expected_income_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();
        $income = ExpectedIncome::factory()->for($user)->create([
            'account_id' => $account->id,
            'received_at' => now(),
            'transaction_id' => Transaction::factory()->for($user)->for($account, 'account')->create([
                'type' => TransactionType::Income,
            ])->id,
        ]);

        $this->actingAs($user)->delete(route('expected-incomes.destroy', $income))
            ->assertRedirect()
            ->assertSessionHasErrors('expected_income');

        $this->assertDatabaseCount('expected_incomes', 1);
    }

    public function test_dashboard_lists_pending_expected_incomes(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();
        ExpectedIncome::factory()->for($user)->create([
            'account_id' => $account->id,
            'description' => 'Adiantamento salarial',
            'received_at' => null,
        ]);
        ExpectedIncome::factory()->for($user)->create([
            'account_id' => $account->id,
            'received_at' => now(),
            'transaction_id' => Transaction::factory()->for($user)->for($account, 'account')->create([
                'type' => TransactionType::Income,
            ])->id,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('expectedIncomes.0.description', 'Adiantamento salarial')
                ->where('expectedIncomes.0.received', false));
    }
}
