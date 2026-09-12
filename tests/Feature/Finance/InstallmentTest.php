<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Installment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InstallmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_an_installment_series(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();

        $this->actingAs($user)->post(route('installments.store'), [
            'type' => TransactionType::Expense->value,
            'account_id' => $account->id,
            'amount' => '199,90',
            'total_count' => '12',
            'starts_on' => '2026-09-01',
            'description' => 'Celular',
        ])->assertRedirect(route('installments.index'));

        $installment = $user->installments()->firstOrFail();

        $this->assertSame($account->id, $installment->account_id);
        $this->assertSame('199.9000', $installment->amount);
        $this->assertSame(12, $installment->total_count);
        $this->assertSame(12, $installment->remaining_count);
        $this->assertSame('2026-09-01', $installment->next_due_date->format('Y-m-d'));
    }

    public function test_installment_requires_at_least_two_installments(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();

        $this->actingAs($user)->post(route('installments.store'), [
            'type' => TransactionType::Expense->value,
            'account_id' => $account->id,
            'amount' => '100.0000',
            'total_count' => '1',
            'starts_on' => '2026-09-01',
        ])->assertSessionHasErrors('total_count');

        $this->assertDatabaseCount('installments', 0);
    }

    public function test_installment_index_lists_series(): void
    {
        $user = User::factory()->create();
        Installment::factory()->for($user)->create([
            'total_count' => 10,
            'remaining_count' => 4,
            'amount' => '99.9000',
            'total_amount' => '999.0000',
            'description' => 'Bike',
        ]);

        $this->actingAs($user)
            ->get(route('installments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Installments/Index')
                ->has('installments', 1)
                ->where('installments.0.remaining_count', 4)
                ->where('installments.0.is_finished', false)
                ->where('installments.0.total_amount', '999.0000')
                ->where('installments.0.paid_count', 6)
                ->where('installments.0.current_parcela', 7)
                ->where('installments.0.total_paid', '599.4000')
                ->where('installments.0.total_remaining', '399.6000')
                ->has('installments.0.schedule', 10)
                ->where('installments.0.schedule.6.number', 7)
                ->where('installments.0.schedule.6.status', 'pendente'));
    }

    public function test_user_cannot_delete_another_users_installment(): void
    {
        $user = User::factory()->create();
        $installment = Installment::factory()->create();

        $this->actingAs($user)->delete(route('installments.destroy', $installment))->assertForbidden();
        $this->assertDatabaseCount('installments', 1);
    }
}
