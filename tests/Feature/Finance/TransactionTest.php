<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
