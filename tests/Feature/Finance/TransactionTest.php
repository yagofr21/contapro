<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_an_expense(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create([
            'type' => CategoryType::Expense,
        ]);

        $this->actingAs($user)->post(route('transactions.store'), [
            'type' => 'expense',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => '1.289,90',
            'transaction_date' => '2026-09-04',
            'description' => 'Mercado',
        ])->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => TransactionType::Expense->value,
            'amount' => '1289.9000',
            'description' => 'Mercado',
        ]);
    }

    public function test_index_marks_future_transactions_and_returns_filtered_totals(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');

        try {
            $user = User::factory()->create();
            $account = FinancialAccount::factory()->for($user)->create(['currency' => 'BRL']);
            $incomeCategory = Category::factory()->for($user)->create(['type' => CategoryType::Income]);
            $expenseCategory = Category::factory()->for($user)->create(['type' => CategoryType::Expense]);

            Transaction::factory()->for($user)->for($account, 'account')->create([
                'category_id' => $incomeCategory->id,
                'type' => TransactionType::Income,
                'amount' => '100.0000',
                'transaction_date' => '2026-09-10',
                'description' => 'Salário',
            ]);
            Transaction::factory()->for($user)->for($account, 'account')->create([
                'category_id' => $expenseCategory->id,
                'type' => TransactionType::Expense,
                'amount' => '40.0000',
                'transaction_date' => '2026-09-11',
                'description' => 'Mercado',
            ]);
            Transaction::factory()->for($user)->for($account, 'account')->create([
                'category_id' => $expenseCategory->id,
                'type' => TransactionType::Expense,
                'amount' => '20.0000',
                'transaction_date' => '2026-09-20',
                'description' => 'Futura',
            ]);
            Transaction::factory()->for($user)->for($account, 'account')->create([
                'type' => TransactionType::TransferOut,
                'amount' => '30.0000',
                'transaction_date' => '2026-09-12',
                'description' => 'Reserva',
            ]);
            Transaction::factory()->create([
                'type' => TransactionType::Income,
                'amount' => '999.0000',
            ]);

            $this->actingAs($user)
                ->get(route('transactions.index'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Transactions/Index')
                    ->where('transactions.total', 4)
                    ->where('transactions.data.0.description', 'Futura')
                    ->where('transactions.data.0.is_future', true)
                    ->where('summaries.0.currency', 'BRL')
                    ->where('summaries.0.income', '100.0000')
                    ->where('summaries.0.expenses', '60.0000')
                    ->where('summaries.0.transfers', '30.0000')
                    ->where('summaries.0.net', '40.0000')
                    ->where('summaries.0.transaction_count', 4));

            $this->actingAs($user)
                ->get(route('transactions.index', ['type' => 'expense', 'category_id' => $expenseCategory->id]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('transactions.total', 2)
                    ->where('summaries.0.income', '0.0000')
                    ->where('summaries.0.expenses', '60.0000')
                    ->where('summaries.0.transfers', '0.0000')
                    ->where('summaries.0.net', '-60.0000')
                    ->where('summaries.0.transaction_count', 2));

            $this->actingAs($user)
                ->get(route('transactions.index', ['status' => 'future']))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('filters.status', 'future')
                    ->where('transactions.total', 1)
                    ->where('transactions.data.0.description', 'Futura')
                    ->where('summaries.0.expenses', '20.0000'));

            $this->actingAs($user)
                ->get(route('transactions.index', ['status' => 'realized']))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('filters.status', 'realized')
                    ->where('transactions.total', 3)
                    ->where('summaries.0.income', '100.0000')
                    ->where('summaries.0.expenses', '40.0000'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_quick_create_from_dashboard_redirects_back_to_dashboard(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create([
            'type' => CategoryType::Expense,
        ]);

        $this->actingAs($user)->post(route('transactions.store'), [
            'type' => 'expense',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => '25.00',
            'transaction_date' => '2026-09-04',
            'from_dashboard' => true,
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => TransactionType::Expense->value,
            'amount' => '25.0000',
        ]);
    }

    public function test_user_cannot_use_another_users_account_or_category(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($user)->post(route('transactions.store'), [
            'type' => 'expense',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => '10.00',
            'transaction_date' => '2026-09-04',
        ])->assertSessionHasErrors(['account_id', 'category_id']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transfer_creates_two_atomic_entries(): void
    {
        $user = User::factory()->create();
        $source = FinancialAccount::factory()->for($user)->create();
        $destination = FinancialAccount::factory()->for($user)->create();

        $this->actingAs($user)->post(route('transactions.store'), [
            'type' => 'transfer',
            'account_id' => $source->id,
            'destination_account_id' => $destination->id,
            'amount' => '250.0000',
            'transaction_date' => '2026-09-04',
            'description' => 'Reserva',
        ])->assertRedirect(route('transactions.index'));

        $entries = Transaction::query()->whereBelongsTo($user)->orderBy('id')->get();

        $this->assertCount(2, $entries);
        $this->assertSame(TransactionType::TransferOut, $entries[0]->type);
        $this->assertSame(TransactionType::TransferIn, $entries[1]->type);
        $this->assertSame($entries[0]->transfer_id, $entries[1]->transfer_id);
        $this->assertNotNull($entries[0]->transfer_id);

        $this->actingAs($user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('transactions.total', 1)
                ->where('summaries.0.income', '0.0000')
                ->where('summaries.0.expenses', '0.0000')
                ->where('summaries.0.transfers', '250.0000')
                ->where('summaries.0.net', '0.0000'));
    }

    public function test_transfer_rejects_same_account_and_different_currencies(): void
    {
        $user = User::factory()->create();
        $source = FinancialAccount::factory()->for($user)->create(['currency' => 'BRL']);
        $destination = FinancialAccount::factory()->for($user)->create(['currency' => 'USD']);

        $payload = [
            'type' => 'transfer',
            'account_id' => $source->id,
            'destination_account_id' => $source->id,
            'amount' => '250.0000',
            'transaction_date' => '2026-09-04',
        ];

        $this->actingAs($user)
            ->post(route('transactions.store'), $payload)
            ->assertSessionHasErrors(['destination_account_id']);

        $this->actingAs($user)
            ->post(route('transactions.store'), [
                ...$payload,
                'destination_account_id' => $destination->id,
            ])
            ->assertSessionHasErrors(['destination_account_id']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_credit_card_payment_is_a_transfer_not_income_or_expense(): void
    {
        $user = User::factory()->create();
        $checking = FinancialAccount::factory()->for($user)->create(['currency' => 'BRL']);
        $card = FinancialAccount::factory()->for($user)->creditCard()->create(['currency' => 'BRL']);

        $this->actingAs($user)->post(route('transactions.store'), [
            'type' => 'transfer',
            'account_id' => $checking->id,
            'destination_account_id' => $card->id,
            'amount' => '500.0000',
            'transaction_date' => '2026-09-04',
            'description' => 'Pagamento do cartão',
        ])->assertRedirect(route('transactions.index'));

        $this->actingAs($user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('transactions.total', 1)
                ->where('transactions.data.0.type', 'transfer')
                ->where('summaries.0.income', '0.0000')
                ->where('summaries.0.expenses', '0.0000')
                ->where('summaries.0.transfers', '500.0000')
                ->where('summaries.0.net', '0.0000'));
    }

    public function test_updating_and_deleting_a_transfer_changes_both_entries(): void
    {
        $user = User::factory()->create();
        $source = FinancialAccount::factory()->for($user)->create();
        $destination = FinancialAccount::factory()->for($user)->create();

        $this->actingAs($user)->post(route('transactions.store'), [
            'type' => 'transfer',
            'account_id' => $source->id,
            'destination_account_id' => $destination->id,
            'amount' => '100.0000',
            'transaction_date' => '2026-09-04',
        ]);

        $outgoing = Transaction::query()->where('type', TransactionType::TransferOut)->sole();

        $this->actingAs($user)->put(route('transactions.update', $outgoing), [
            'type' => 'transfer',
            'account_id' => $source->id,
            'destination_account_id' => $destination->id,
            'amount' => '175.5000',
            'transaction_date' => '2026-09-05',
            'description' => 'Atualizada',
        ])->assertRedirect(route('transactions.index'));

        $this->assertSame(2, Transaction::query()->where('amount', '175.5000')->count());

        $this->actingAs($user)
            ->delete(route('transactions.destroy', $outgoing))
            ->assertRedirect(route('transactions.index'));

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_user_cannot_update_another_users_transaction(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create();

        $this->actingAs($user)->put(route('transactions.update', $transaction), [
            'type' => 'expense',
            'account_id' => $transaction->account_id,
            'amount' => '10.00',
            'transaction_date' => '2026-09-04',
        ])->assertForbidden();
    }
}
