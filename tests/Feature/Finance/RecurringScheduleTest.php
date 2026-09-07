<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Finance\Enums\ScheduleFrequency;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\TransactionSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RecurringScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_recurring_expense_schedule(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create();

        $this->actingAs($user)->post(route('recurring.store'), [
            'type' => TransactionType::Expense->value,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => '1.250,50',
            'frequency' => ScheduleFrequency::Monthly->value,
            'starts_on' => '2026-09-01',
            'description' => 'Assinatura',
        ])->assertRedirect(route('recurring.index'));

        $schedule = $user->transactionSchedules()->firstOrFail();

        $this->assertSame($account->id, $schedule->account_id);
        $this->assertSame($category->id, $schedule->category_id);
        $this->assertSame('1250.5000', $schedule->amount);
        $this->assertSame(ScheduleFrequency::Monthly, $schedule->frequency);
        $this->assertSame('2026-09-01', $schedule->next_run_date->format('Y-m-d'));
        $this->assertTrue($schedule->is_active);
    }

    public function test_schedule_rejects_category_when_transfer(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();
        $destination = FinancialAccount::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create();

        $this->actingAs($user)->post(route('recurring.store'), [
            'type' => 'transfer',
            'account_id' => $account->id,
            'destination_account_id' => $destination->id,
            'category_id' => $category->id,
            'amount' => '100.0000',
            'frequency' => ScheduleFrequency::Monthly->value,
            'starts_on' => '2026-09-01',
        ])->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('transaction_schedules', 0);
    }

    public function test_recurring_index_lists_active_schedules(): void
    {
        $user = User::factory()->create();
        TransactionSchedule::factory()->for($user)->create([
            'frequency' => ScheduleFrequency::Weekly,
            'amount' => '80.0000',
            'description' => 'Mensalidade',
            'type' => TransactionType::Expense,
        ]);

        $this->actingAs($user)
            ->get(route('recurring.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Recurring/Index')
                ->has('schedules', 1)
                ->where('schedules.0.amount', '80.0000')
                ->where('schedules.0.frequency', ScheduleFrequency::Weekly->value));
    }

    public function test_user_cannot_update_another_users_schedule(): void
    {
        $user = User::factory()->create();
        $schedule = TransactionSchedule::factory()->create();

        $this->actingAs($user)->put(route('recurring.update', $schedule), [
            'type' => TransactionType::Expense->value,
            'account_id' => $schedule->account_id,
            'amount' => '50.0000',
            'frequency' => ScheduleFrequency::Daily->value,
            'starts_on' => '2026-09-01',
        ])->assertForbidden();
    }

    public function test_user_cannot_delete_another_users_schedule(): void
    {
        $user = User::factory()->create();
        $schedule = TransactionSchedule::factory()->create();

        $this->actingAs($user)->delete(route('recurring.destroy', $schedule))->assertForbidden();
        $this->assertDatabaseCount('transaction_schedules', 1);
    }
}
