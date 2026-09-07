<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Finance\Actions\ProcessInstallments;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Installment;
use App\Modules\Finance\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessInstallmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_installment_materializes_transaction_and_decrements(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();
        $installment = Installment::factory()->for($user)->create([
            'account_id' => $account->id,
            'type' => TransactionType::Expense,
            'amount' => '199.9000',
            'total_count' => 10,
            'remaining_count' => 10,
            'next_due_date' => '2026-09-01',
            'description' => 'Celular',
        ]);

        $created = app(ProcessInstallments::class)->handle(CarbonImmutable::parse('2026-09-15'));

        $this->assertSame(1, $created);

        $transaction = Transaction::query()
            ->where('user_id', $user->id)
            ->where('account_id', $account->id)
            ->firstOrFail();

        $this->assertSame(TransactionType::Expense, $transaction->type);
        $this->assertSame('199.9000', $transaction->amount);
        $this->assertSame('2026-09-01', $transaction->transaction_date->format('Y-m-d'));
        $this->assertSame('Celular (1/10)', $transaction->description);

        $installment->refresh();
        $this->assertSame(9, $installment->remaining_count);
        $this->assertSame('2026-10-01', $installment->next_due_date->format('Y-m-d'));
    }

    public function test_processing_is_idempotent_within_same_day(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();
        Installment::factory()->for($user)->create([
            'type' => TransactionType::Expense,
            'amount' => '50.0000',
            'total_count' => 3,
            'remaining_count' => 3,
            'next_due_date' => '2026-09-01',
            'description' => 'Ferramenta',
        ]);

        $action = app(ProcessInstallments::class);
        $reference = CarbonImmutable::parse('2026-09-15');

        $this->assertSame(1, $action->handle($reference));
        $this->assertSame(0, $action->handle($reference));

        $this->assertSame(1, Transaction::query()->count());
    }

    public function test_finished_installment_stops_processing(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();
        Installment::factory()->for($user)->create([
            'type' => TransactionType::Income,
            'amount' => '100.0000',
            'total_count' => 2,
            'remaining_count' => 1,
            'next_due_date' => '2026-09-01',
            'description' => 'Venda',
        ]);

        $this->assertSame(1, app(ProcessInstallments::class)->handle(CarbonImmutable::parse('2026-09-15')));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => TransactionType::Income->value,
            'description' => 'Venda (2/2)',
        ]);

        // Remaining 0 -> nothing more processed even on a later date.
        $this->assertSame(0, app(ProcessInstallments::class)->handle(CarbonImmutable::parse('2026-10-15')));
    }
}
