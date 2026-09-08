<?php

namespace Tests\Feature\Finance;

use App\Enums\Currency;
use App\Models\User;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialGoal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinancialGoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_financial_goal(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();

        $this->actingAs($user)->post(route('goals.store'), [
            'name' => 'Reserva de emergencia',
            'description' => 'Seis meses de custo de vida',
            'target_amount' => '10.000,00',
            'currency' => Currency::BRL->value,
            'account_id' => $account->id,
            'target_date' => '2027-09-01',
        ])->assertRedirect(route('goals.index'));

        $this->assertDatabaseHas('financial_goals', [
            'user_id' => $user->id,
            'name' => 'Reserva de emergencia',
            'account_id' => $account->id,
            'target_amount' => '10000.0000',
        ]);
    }

    public function test_goal_index_computes_progress_for_linked_account(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create([
            'initial_balance' => '1000.0000',
        ]);
        FinancialGoal::factory()->for($user)->create([
            'account_id' => $account->id,
            'target_amount' => '2000.0000',
        ]);

        $this->actingAs($user)
            ->get(route('goals.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Goals/Index')
                ->has('goals', 1)
                ->where('goals.0.saved_amount', '1000.0000')
                ->where('goals.0.progress', '50.0000')
                ->where('goals.0.remaining', '1000.0000')
                ->where('goals.0.is_achieved', false));
    }

    public function test_unlinked_goal_sums_all_accounts_in_currency(): void
    {
        $user = User::factory()->create();
        FinancialAccount::factory()->for($user)->create([
            'currency' => Currency::BRL,
            'initial_balance' => '400.0000',
        ]);
        FinancialAccount::factory()->for($user)->create([
            'currency' => Currency::BRL,
            'initial_balance' => '600.0000',
        ]);
        FinancialAccount::factory()->for($user)->create([
            'currency' => Currency::EUR,
            'initial_balance' => '300.0000',
        ]);
        FinancialGoal::factory()->for($user)->create([
            'account_id' => null,
            'currency' => Currency::BRL,
            'target_amount' => '2000.0000',
        ]);

        $this->actingAs($user)
            ->get(route('goals.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('goals.0.saved_amount', '1000.0000')
                ->where('goals.0.progress', '50.0000')
                ->where('goals.0.is_achieved', false));
    }

    public function test_goal_progress_clamps_at_100_with_remaining_zero(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create([
            'initial_balance' => '2500.0000',
        ]);
        FinancialGoal::factory()->for($user)->create([
            'account_id' => $account->id,
            'target_amount' => '2000.0000',
        ]);

        $this->actingAs($user)
            ->get(route('goals.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('goals.0.saved_amount', '2500.0000')
                ->where('goals.0.progress', '100.0000')
                ->where('goals.0.remaining', '0.0000')
                ->where('goals.0.is_achieved', true));
    }

    public function test_goal_only_accepts_owned_accounts(): void
    {
        $user = User::factory()->create();
        $otherAccount = FinancialAccount::factory()->create();

        $this->actingAs($user)->post(route('goals.store'), [
            'name' => 'Meta invalida',
            'target_amount' => '1000.0000',
            'currency' => Currency::BRL->value,
            'target_date' => '2027-09-01',
            'account_id' => $otherAccount->id,
        ])->assertSessionHasErrors('account_id');
    }

    public function test_user_cannot_update_another_users_goal(): void
    {
        $user = User::factory()->create();
        $goal = FinancialGoal::factory()->create([
            'account_id' => null,
        ]);

        $this->actingAs($user)->put(route('goals.update', $goal), [
            'name' => 'Invasao',
            'target_amount' => '100.0000',
            'currency' => Currency::BRL->value,
            'target_date' => '2027-09-01',
        ])->assertForbidden();
    }
}
