<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Finance\Actions\GenerateScheduledTransactions;
use App\Modules\Finance\Enums\ScheduleFrequency;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Models\TransactionSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateScheduledTransactionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_schedule_materializes_a_transaction_and_advances(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();
        $schedule = TransactionSchedule::factory()->for($user)->create([
            'account_id' => $account->id,
            'type' => TransactionType::Expense,
            'amount' => '150.0000',
            'frequency' => ScheduleFrequency::Monthly,
            'starts_on' => '2026-09-01',
            'next_run_date' => '2026-09-01',
            'description' => 'Aluguel',
        ]);

        $created = app(GenerateScheduledTransactions::class)->handle(CarbonImmutable::parse('2026-09-15'));

        $this->assertSame(1, $created);

        $transaction = Transaction::query()
            ->where('user_id', $user->id)
            ->where('account_id', $account->id)
            ->firstOrFail();

        $this->assertSame(TransactionType::Expense, $transaction->type);
        $this->assertSame('150.0000', $transaction->amount);
        $this->assertSame('2026-09-01', $transaction->transaction_date->format('Y-m-d'));
        $this->assertSame('Aluguel', $transaction->description);

        $schedule->refresh();
        $this->assertSame('2026-10-01', $schedule->next_run_date->format('Y-m-d'));
        $this->assertTrue($schedule->is_active);
    }

    public function test_generation_is_idempotent_within_the_same_day(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();
        TransactionSchedule::factory()->for($user)->create([
            'type' => TransactionType::Income,
            'amount' => '5000.0000',
            'frequency' => ScheduleFrequency::Monthly,
            'starts_on' => '2026-09-01',
            'next_run_date' => '2026-09-01',
        ]);

        $action = app(GenerateScheduledTransactions::class);
        $reference = CarbonImmutable::parse('2026-09-15');

        $this->assertSame(1, $action->handle($reference));
        $this->assertSame(0, $action->handle($reference));

        $this->assertSame(1, Transaction::query()->count());
    }

    public function test_schedule_with_ends_on_is_deactivated_after_last_occurrence(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();
        $schedule = TransactionSchedule::factory()->for($user)->create([
            'type' => TransactionType::Expense,
            'amount' => '10.0000',
            'frequency' => ScheduleFrequency::Daily,
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-02',
            'next_run_date' => '2026-09-02',
        ]);

        $this->assertSame(1, app(GenerateScheduledTransactions::class)->handle(CarbonImmutable::parse('2026-09-03')));

        $schedule->refresh();
        $this->assertFalse($schedule->is_active);
        $this->assertSame(1, Transaction::query()->count());
    }

    public function test_inactive_schedule_does_not_generate(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();
        TransactionSchedule::factory()->for($user)->create([
            'type' => TransactionType::Expense,
            'amount' => '99.0000',
            'frequency' => ScheduleFrequency::Monthly,
            'starts_on' => '2026-09-01',
            'next_run_date' => '2026-09-01',
            'is_active' => false,
        ]);

        $this->assertSame(0, app(GenerateScheduledTransactions::class)->handle(CarbonImmutable::parse('2026-09-15')));
        $this->assertSame(0, Transaction::query()->count());
    }
}
