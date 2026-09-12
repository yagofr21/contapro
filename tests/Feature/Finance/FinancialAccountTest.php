<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Finance\Enums\FinancialAccountType;
use App\Modules\Finance\Models\FinancialAccount;
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
}
