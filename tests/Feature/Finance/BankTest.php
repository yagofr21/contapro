<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Finance\Models\Bank;
use App\Modules\Finance\Models\FinancialAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BankTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_update_and_delete_a_bank(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('banks.store'), [
            'code' => 'My_Bank ',
            'label' => ' Meu Banco ',
            'color' => '#aabbcc',
            'initials' => 'mb',
            'is_active' => true,
        ])->assertRedirect(route('banks.index'));

        $bank = Bank::query()->sole();

        $this->assertDatabaseHas('banks', [
            'id' => $bank->id,
            'code' => 'my_bank',
            'label' => 'Meu Banco',
            'initials' => 'MB',
        ]);

        $this->actingAs($user)->put(route('banks.update', $bank), [
            'code' => 'renamed_code',
            'label' => 'Meu Banco 2',
            'color' => '#123456',
            'initials' => 'MB2',
            'is_active' => false,
        ])->assertRedirect(route('banks.index'));

        $this->assertDatabaseHas('banks', [
            'id' => $bank->id,
            'code' => 'my_bank',
            'label' => 'Meu Banco 2',
            'color' => '#123456',
            'initials' => 'MB2',
            'is_active' => false,
        ]);

        $this->actingAs($user)->delete(route('banks.destroy', $bank))
            ->assertRedirect(route('banks.index'));

        $this->assertDatabaseMissing('banks', ['id' => $bank->id]);
    }

    public function test_bank_code_must_match_snake_case_and_be_unique(): void
    {
        $user = User::factory()->create();
        Bank::factory()->create(['code' => 'nubank']);

        $this->actingAs($user)->post(route('banks.store'), [
            'code' => 'Nubank',
            'label' => 'Nubank',
            'initials' => 'NB',
        ])->assertSessionHasErrors(['code']);

        $this->actingAs($user)->post(route('banks.store'), [
            'code' => 'x y',
            'label' => 'X',
            'initials' => 'XY',
        ])->assertSessionHasErrors(['code']);
    }

    public function test_bank_cannot_be_deleted_while_in_use_by_account(): void
    {
        $user = User::factory()->create();
        $bank = Bank::factory()->create(['code' => 'nubank']);
        FinancialAccount::factory()->for($user)->create(['bank' => 'nubank']);

        $this->actingAs($user)->delete(route('banks.destroy', $bank))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('banks', ['id' => $bank->id]);
    }

    public function test_index_lists_banks_with_usage_count(): void
    {
        $user = User::factory()->create();
        Bank::factory()->create(['code' => 'my_bank', 'label' => 'Meu Banco']);
        FinancialAccount::factory()->for($user)->create(['bank' => 'my_bank']);

        $this->actingAs($user)->get(route('banks.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Banks/Index')
                ->has('banks', 1)
                ->where('banks.0.code', 'my_bank')
                ->where('banks.0.accounts_count', 1));
    }

    public function test_guest_cannot_manage_banks(): void
    {
        $this->post(route('banks.store'), [
            'code' => 'my_bank',
            'label' => 'Meu Banco',
            'initials' => 'MB',
        ])->assertRedirect(route('login'));
    }
}
