<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Queries\SpendingProjectionQuery;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SpendingProjectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_baseline_uses_three_complete_months_and_excludes_transfers_current_month_and_other_users(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-06'));
        $user = User::factory()->create(['timezone' => 'Asia/Tokyo']);
        $account = FinancialAccount::factory()->for($user)->create(['currency' => 'BRL', 'created_at' => '2026-07-01']);
        foreach (['2026-07-10', '2026-08-10', '2026-09-10'] as $date) {
            Transaction::factory()->for($user)->for($account, 'account')->create(['transaction_date' => $date, 'type' => TransactionType::Income, 'amount' => '3000']);
            Transaction::factory()->for($user)->for($account, 'account')->create(['transaction_date' => $date, 'type' => TransactionType::Expense, 'amount' => '2000']);
        }
        Transaction::factory()->for($user)->for($account, 'account')->create(['transaction_date' => '2026-09-20', 'type' => TransactionType::TransferOut, 'amount' => '5000']);
        Transaction::factory()->for($user)->for($account, 'account')->create(['transaction_date' => '2026-10-01', 'type' => TransactionType::Expense, 'amount' => '9000']);
        Transaction::factory()->create(['transaction_date' => '2026-09-10', 'amount' => '50000']);
        $result = app(SpendingProjectionQuery::class)->forUser($user);
        $this->assertSame(3, $result[0]['months_count']);
        $this->assertSame('3000.0000', $result[0]['monthly_income']);
        $this->assertSame('2000.0000', $result[0]['monthly_expenses']);
    }

    public function test_no_complete_history_does_not_invent_a_monthly_average(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-06'));
        $user = User::factory()->create();
        FinancialAccount::factory()->for($user)->create(['currency' => 'BRL']);
        $result = app(SpendingProjectionQuery::class)->forUser($user);
        $this->assertSame(0, $result[0]['months_count']);
        $this->assertNull($result[0]['monthly_income']);
        $this->assertNull($result[0]['monthly_expenses']);
    }

    public function test_dashboard_available_balance_excludes_future_transactions(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-06'));
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create(['type' => 'checking', 'currency' => 'BRL', 'initial_balance' => '1000']);
        Transaction::factory()->for($user)->for($account, 'account')->create(['transaction_date' => '2026-11-01', 'type' => TransactionType::Expense, 'amount' => '200']);
        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (AssertableInertia $page) => $page->where('financialSummaries.0.available_balance', '1000.0000')->where('financialSummaries.0.scheduled_balance', '800.0000'));
    }
}
