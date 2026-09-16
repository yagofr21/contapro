<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Finance\Models\Installment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InstallmentIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_users_installments(): void
    {
        $user = User::factory()->create();
        Installment::factory()->for($user)->create([
            'total_count' => 6,
            'remaining_count' => 3,
            'amount' => '150.0000',
            'total_amount' => '900.0000',
            'description' => 'Notebook',
        ]);

        $this->actingAs($user)
            ->get(route('installments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Installments/Index')
                ->has('installments', 1)
                ->where('installments.0.description', 'Notebook')
                ->where('installments.0.remaining_count', 3)
                ->has('installments.0.schedule', 6));
    }

    public function test_index_renders_empty_state_when_user_has_no_installments(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('installments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Installments/Index')
                ->has('installments', 0));
    }

    public function test_index_handles_incomplete_installments_without_crashing(): void
    {
        $user = User::factory()->create();
        Installment::factory()->for($user)->create([
            'total_count' => 0,
            'remaining_count' => 0,
            'amount' => '0.0000',
            'total_amount' => '0.0000',
            'next_due_date' => now()->startOfMonth(),
        ]);

        $this->actingAs($user)
            ->get(route('installments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Installments/Index')
                ->has('installments', 1)
                ->has('installments.0.schedule', 1)
                ->where('installments.0.current_parcela', null));
    }

    public function test_index_is_scoped_to_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Installment::factory()->for($other)->create();

        $this->actingAs($user)
            ->get(route('installments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Installments/Index')
                ->has('installments', 0));
    }
}
