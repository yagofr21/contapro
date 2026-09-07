<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Finance\Enums\ScheduleFrequency;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Installment;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Models\TransactionSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgendaProjectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_agenda_lists_schedule_and_installment_events(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();
        TransactionSchedule::factory()->for($user)->create([
            'account_id' => $account->id,
            'type' => TransactionType::Expense,
            'amount' => '150.0000',
            'frequency' => ScheduleFrequency::Monthly,
            'starts_on' => '2026-09-01',
            'next_run_date' => '2026-09-10',
            'description' => 'Aluguel',
        ]);
        Installment::factory()->for($user)->create([
            'account_id' => $account->id,
            'type' => TransactionType::Expense,
            'amount' => '199.9000',
            'total_count' => 3,
            'remaining_count' => 3,
            'next_due_date' => '2026-09-05',
            'description' => 'Celular',
        ]);

        $this->travelTo(CarbonImmutable::parse('2026-09-01'));

        $this->actingAs($user)
            ->get(route('agenda.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Agenda/Index')
                ->has('events', 4)
                ->where('events.0.date', '2026-09-05')
                ->where('events.0.kind', 'installment')
                ->where('events.1.date', '2026-09-10')
                ->where('events.1.kind', 'schedule')
                ->where('events.2.date', '2026-10-05')
                ->where('events.3.date', '2026-10-10'));
    }

    public function test_projection_computes_projected_balance(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create([
            'initial_balance' => '1000.0000',
        ]);
        Transaction::factory()->for($user)->for($account, 'account')->create([
            'type' => TransactionType::Income,
            'amount' => '500.0000',
        ]);
        TransactionSchedule::factory()->for($user)->create([
            'account_id' => $account->id,
            'type' => TransactionType::Expense,
            'amount' => '200.0000',
            'frequency' => ScheduleFrequency::Monthly,
            'starts_on' => '2026-09-01',
            'next_run_date' => '2026-09-10',
            'description' => 'Conta',
        ]);

        $this->travelTo(CarbonImmutable::parse('2026-09-01'));

        $this->actingAs($user)
            ->get(route('agenda.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Agenda/Index')
                ->has('projection', 1)
                ->where('projection.0.balance', '1500.0000')
                ->where('projection.0.projected_balance', '1100.0000'));
    }
}
