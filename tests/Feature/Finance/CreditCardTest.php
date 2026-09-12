<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Finance\Actions\CreateInstallment;
use App\Modules\Finance\Actions\ProcessInstallments;
use App\Modules\Finance\Enums\FinancialAccountType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Installment;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Queries\CreditCardSummaryQuery;
use App\Modules\Finance\Support\InstallmentMath;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CreditCardTest extends TestCase
{
    use RefreshDatabase;

    private function card(User $user): FinancialAccount
    {
        return FinancialAccount::factory()->for($user)->creditCard()->create();
    }

    public function test_utilized_limit_is_current_invoice_plus_future_installments(): void
    {
        $user = User::factory()->create();
        $card = $this->card($user);

        $card->transactions()->create([
            'user_id' => $user->id,
            'type' => TransactionType::Expense,
            'amount' => '200.0000',
            'transaction_date' => '2026-09-10',
        ]);

        $installment = Installment::factory()->for($user)->create([
            'account_id' => $card->id,
            'amount' => '333.3300',
            'total_amount' => '1000.0000',
            'total_count' => 3,
            'remaining_count' => 3,
            'next_due_date' => '2026-09-10',
        ]);

        $summary = (new CreditCardSummaryQuery)->forAccount($card, CarbonImmutable::parse('2026-09-10'));

        // Invoice period: 2026-08-16 .. 2026-09-15 (closing day 15).
        $this->assertSame('2026-08-16', $summary['current_invoice_start']);
        $this->assertSame('2026-09-15', $summary['current_invoice_end']);

        // 200.00 direct + 333.33 (parcela 1) inside the current invoice.
        $this->assertSame('533.3300', $summary['current_invoice']);
        // Parcelas 2 and 3 (Oct/Nov) are future.
        $this->assertSame('666.6700', $summary['future_invoices']);
        // Utilized = current + future = full purchase + direct expense.
        $this->assertSame('1200.0000', $summary['utilized']);
        $this->assertSame('3800.0000', $summary['available']);
        $this->assertSame(24, $summary['utilization']);
    }

    public function test_installment_rounding_keeps_total_and_no_double_counting(): void
    {
        $split = InstallmentMath::split(100_000, 3);
        $this->assertSame(['base' => 33_333, 'last' => 33_334], $split);

        $user = User::factory()->create();
        $card = $this->card($user);

        $installment = app(CreateInstallment::class)->handle($user, [
            'type' => 'expense',
            'account_id' => $card->id,
            'total_amount' => '1000.0000',
            'total_count' => 3,
            'starts_on' => '2026-09-10',
            'description' => 'Notebook',
        ]);

        $this->assertSame('333.3300', $installment->amount);
        $this->assertSame('333.3300', $installment->amountForOrdinal(1));
        $this->assertSame('333.3300', $installment->amountForOrdinal(2));
        $this->assertSame('333.3400', $installment->amountForOrdinal(3));
        $this->assertSame('1000.0000', $installment->totalAmountValue());

        // Each ProcessInstallments call materializes one parcela per installment.
        $process = app(ProcessInstallments::class);
        $process->handle(CarbonImmutable::parse('2026-09-15'));
        $process->handle(CarbonImmutable::parse('2026-10-15'));
        $process->handle(CarbonImmutable::parse('2026-11-15'));

        $this->assertDatabaseCount('transactions', 3);

        // Every parcela is an expense with the installment link.
        $this->assertSame(3, Transaction::query()->whereNotNull('installment_id')->count());

        // Reports sum parcelas by month and never the total again.
        $total = $user->transactions()
            ->where('account_id', $card->id)
            ->where('type', TransactionType::Expense->value)
            ->sum('amount');
        $this->assertSame('1000.0000', number_format((float) $total, 4, '.', ''));

        $installment->refresh();
        $this->assertSame(0, $installment->remaining_count);
    }

    public function test_user_can_install_an_expense_through_the_transaction_modal(): void
    {
        $user = User::factory()->create();
        $card = $this->card($user);

        $this->actingAs($user)->post(route('transactions.store'), [
            'type' => 'expense',
            'account_id' => $card->id,
            'amount' => '3.000,00',
            'total_count' => 10,
            'install_in' => 1,
            'first_installment_date' => now()->toDateString(),
            'description' => 'Celular',
        ])->assertRedirect(route('transactions.index'))
            ->assertSessionHas('success', 'Compra parcelada lancada com sucesso.')
            ->assertSessionHas('detail');

        $installment = $user->installments()->firstOrFail();
        $this->assertSame(10, $installment->total_count);
        $this->assertSame('3000.0000', $installment->totalAmountValue());
        // First parcela (today) was due -> materialized right away.
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'account_id' => $card->id,
            'installment_id' => $installment->id,
            'amount' => '300.0000',
            'description' => 'Celular (1/10)',
        ]);
        $this->assertSame(9, $installment->fresh()->remaining_count);
    }

    public function test_install_in_requires_a_credit_card_account(): void
    {
        $user = User::factory()->create();
        $checking = FinancialAccount::factory()->for($user)->create();

        $this->actingAs($user)->post(route('transactions.store'), [
            'type' => 'expense',
            'account_id' => $checking->id,
            'amount' => '100.0000',
            'total_count' => 3,
            'install_in' => 1,
            'first_installment_date' => '2026-09-10',
        ])->assertSessionHasErrors('account_id');

        $this->assertDatabaseCount('installments', 0);
    }

    public function test_store_flashes_type_specific_messages(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();

        $this->actingAs($user)->post(route('transactions.store'), [
            'type' => 'income',
            'account_id' => $account->id,
            'amount' => '1500.0000',
            'transaction_date' => '2026-09-10',
            'description' => 'Salario',
        ])->assertRedirect(route('transactions.index'))
            ->assertSessionHas('success', 'Receita lancada com sucesso.');
    }

    public function test_credit_fields_require_credit_card_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('accounts.store'), [
            'name' => 'Conta corrente',
            'type' => FinancialAccountType::Checking->value,
            'currency' => 'BRL',
            'initial_balance' => '0.0000',
            'credit_limit' => '5000.0000',
        ])->assertSessionHasErrors('type');

        $this->assertDatabaseCount('financial_accounts', 0);
    }

    public function test_account_edit_returns_credit_fields(): void
    {
        $user = User::factory()->create();
        $card = $this->card($user);

        $this->actingAs($user)
            ->get(route('accounts.edit', $card))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('account.credit_limit', '5000.0000')
                ->where('account.credit_closing_day', 15)
                ->where('account.credit_due_day', 22));
    }
}
