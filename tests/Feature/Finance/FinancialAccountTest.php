<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Finance\Enums\FinancialAccountType;
use App\Modules\Finance\Models\ExpectedIncome;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialGoal;
use App\Modules\Finance\Models\Installment;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Models\TransactionSchedule;
use Carbon\CarbonImmutable;
use Database\Seeders\BankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinancialAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_their_own_accounts(): void
    {
        $user = User::factory()->create();
        FinancialAccount::factory()->for($user)->create(['name' => 'Minha conta']);
        FinancialAccount::factory()->create(['name' => 'Conta de terceiro']);

        $this->actingAs($user)
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounts/Index')
                ->has('accounts', 1)
                ->where('accounts.0.name', 'Minha conta'));
    }

    public function test_currency_change_is_blocked_for_accounts_with_financial_records(): void
    {
        $user = User::factory()->create();
        foreach ([
            Transaction::class,
            Installment::class,
            TransactionSchedule::class,
            FinancialGoal::class,
            ExpectedIncome::class,
        ] as $model) {
            $account = FinancialAccount::factory()->for($user)->create(['currency' => 'BRL']);
            $model::factory()->create(['user_id' => $user->id, 'account_id' => $account->id]);
            $this->actingAs($user)->put(route('accounts.update', $account), [
                'name' => 'Renamed',
                'type' => 'checking',
                'currency' => 'USD',
                'initial_balance' => '0',
            ])->assertSessionHasErrors('currency');
            $this->assertSame('BRL', $account->refresh()->currency->value);
        }
    }

    public function test_currency_change_is_blocked_for_recurring_transfer_destination(): void
    {
        $user = User::factory()->create();
        $source = FinancialAccount::factory()->for($user)->create(['currency' => 'BRL']);
        $destination = FinancialAccount::factory()->for($user)->create(['currency' => 'BRL']);
        TransactionSchedule::factory()->create([
            'user_id' => $user->id,
            'account_id' => $source->id,
            'destination_account_id' => $destination->id,
            'type' => 'transfer_out',
        ]);
        $this->actingAs($user)->put(route('accounts.update', $destination), [
            'name' => 'Destination',
            'type' => 'checking',
            'currency' => 'USD',
            'initial_balance' => '0',
        ])->assertSessionHasErrors('currency');
        $this->assertSame('BRL', $destination->refresh()->currency->value);
    }

    public function test_currency_can_change_for_an_account_without_financial_records(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create(['currency' => 'BRL']);
        $this->actingAs($user)->put(route('accounts.update', $account), [
            'name' => 'USD',
            'type' => 'checking',
            'currency' => 'USD',
            'initial_balance' => '0',
        ])->assertSessionHasNoErrors();
        $this->assertSame('USD', $account->refresh()->currency->value);
    }

    public function test_user_can_create_and_update_an_account(): void
    {
        $user = User::factory()->create();
        $this->seed(BankSeeder::class);

        $this->actingAs($user)->post(route('accounts.store'), [
            'name' => 'Reserva',
            'bank' => 'nubank',
            'color' => '#820AD1',
            'type' => FinancialAccountType::Savings->value,
            'currency' => 'BRL',
            'initial_balance' => '1.000,50',
        ])->assertRedirect(route('accounts.index'));

        $account = $user->financialAccounts()->sole();
        $this->assertSame('1000.5000', $account->initial_balance);
        $this->assertSame('nubank', $account->bank);
        $this->assertSame('#820AD1', $account->color);

        $this->actingAs($user)->put(route('accounts.update', $account), [
            'name' => 'Reserva de emergencia',
            'bank' => 'itau',
            'color' => '#EC7001',
            'type' => FinancialAccountType::Savings->value,
            'currency' => 'BRL',
            'initial_balance' => '1.250,50',
            'is_archived' => true,
        ])->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('financial_accounts', [
            'id' => $account->id,
            'name' => 'Reserva de emergencia',
            'bank' => 'itau',
            'color' => '#EC7001',
            'initial_balance' => '1250.5000',
            'is_archived' => true,
        ]);
    }

    public function test_account_bank_and_color_are_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('accounts.store'), [
            'name' => 'Invalida',
            'bank' => 'banco_inexistente',
            'color' => 'vermelho',
            'type' => FinancialAccountType::Checking->value,
            'currency' => 'BRL',
            'initial_balance' => '0',
        ])->assertSessionHasErrors(['bank', 'color']);
    }

    public function test_user_cannot_update_another_users_account(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->create();

        $this->actingAs($user)->put(route('accounts.update', $account), [
            'name' => 'Invasao',
            'type' => FinancialAccountType::Checking->value,
            'currency' => 'BRL',
            'initial_balance' => '0',
        ])->assertForbidden();
    }

    public function test_removing_an_account_uses_soft_delete(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete(route('accounts.destroy', $account))
            ->assertRedirect(route('accounts.index'));

        $this->assertSoftDeleted($account);
    }

    public function test_account_balances_match_dashboard_at_the_users_local_date(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-27 01:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'America/Sao_Paulo']);
        $checking = FinancialAccount::factory()->for($user)->create([
            'name' => 'A Conta', 'type' => FinancialAccountType::Checking,
            'currency' => 'BRL', 'initial_balance' => '1000.0000',
        ]);
        $card = FinancialAccount::factory()->for($user)->creditCard()->create([
            'name' => 'B Cartao', 'currency' => 'BRL', 'initial_balance' => '0',
        ]);
        foreach ([$checking, $card] as $account) {
            Transaction::factory()->for($user)->for($account, 'account')->create([
                'type' => 'expense', 'amount' => '100', 'transaction_date' => '2026-10-26',
            ]);
            Transaction::factory()->for($user)->for($account, 'account')->create([
                'type' => 'income', 'amount' => '999', 'transaction_date' => '2026-10-27',
            ]);
        }
        $this->actingAs($user)->get(route('accounts.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('asOf', '2026-10-26')
                ->where('accounts.0.balance', '900.0000')
                ->where('accounts.1.balance', '-100.0000'));
        $this->get(route('accounts.show', $card))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('account.balance', '-100.0000'));
        $this->get(route('dashboard'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financialSummaries.0.available_balance', '900.0000')
                ->where('accounts.0.balance', '900.0000'));
    }
}
