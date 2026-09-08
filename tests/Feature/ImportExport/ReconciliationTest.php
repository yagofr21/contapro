<?php

namespace Tests\Feature\ImportExport;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use App\Modules\ImportExport\Enums\ReconciliationStatus;
use App\Modules\ImportExport\Models\Reconciliation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_statement_preview_matches_exact_and_window_rules_and_confirm_reconciles(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create([
            'name' => 'Conta corrente',
            'currency' => 'BRL',
            'initial_balance' => '1000',
        ]);
        Transaction::factory()->for($user)->create(['account_id' => $account->id,
            'type' => TransactionType::Expense,
            'amount' => '200.00',
            'transaction_date' => '2026-09-05',
        ]);
        Transaction::factory()->for($user)->create(['account_id' => $account->id,
            'type' => TransactionType::Income,
            'amount' => '500.00',
            'transaction_date' => '2026-09-10',
        ]);
        $csv = implode("\r\n", [
            'Data;Descricao;Valor',
            '05/09/2026;Mercado;-200,00',
            '2026-09-11;Salario;500,00',
        ]);

        $response = $this->actingAs($user)->post(route('reconciliations.store'), [
            'account_id' => $account->id,
            'period_start' => '2026-09-01',
            'statement_date' => '2026-09-30',
            'declared_balance' => '1300',
            'file' => UploadedFile::fake()->createWithContent('extrato.csv', $csv),
        ]);

        $response->assertRedirect();
        $reconciliation = Reconciliation::query()->sole();
        $this->assertSame(ReconciliationStatus::Previewed, $reconciliation->status);
        $this->assertSame('1300.0000', $reconciliation->detected_balance);
        $this->assertSame('0.0000', $reconciliation->delta);
        $this->assertSame(['total' => 2, 'invalid' => 0, 'matched' => 2, 'missing' => 0, 'extras' => 0], $reconciliation->summary);

        $rows = $reconciliation->rows()->orderBy('row_number')->get();
        $this->assertSame('matched', $rows[0]->status);
        $this->assertSame('exact', $rows[0]->match_rule);
        $this->assertSame('matched', $rows[1]->status);
        $this->assertSame('window', $rows[1]->match_rule);

        $this->actingAs($user)
            ->get(route('reconciliations.show', $reconciliation))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reconciliations/Show')
                ->where('reconciliation.status', ReconciliationStatus::Previewed->value));

        $this->actingAs($user)
            ->post(route('reconciliations.confirm', $reconciliation))
            ->assertRedirect(route('reconciliations.show', $reconciliation));

        $reconciliation->refresh();
        $this->assertSame(ReconciliationStatus::Reconciled, $reconciliation->status);
        $this->assertNotNull($reconciliation->confirmed_at);
    }

    public function test_declared_row_without_match_is_missing_and_confirm_marks_divergent(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create(['initial_balance' => '1000']);
        Transaction::factory()->for($user)->create(['account_id' => $account->id,
            'type' => TransactionType::Expense,
            'amount' => '200.00',
            'transaction_date' => '2026-09-05',
        ]);
        Transaction::factory()->for($user)->create(['account_id' => $account->id,
            'type' => TransactionType::Income,
            'amount' => '500.00',
            'transaction_date' => '2026-09-10',
        ]);
        $csv = implode("\r\n", [
            'Data;Descricao;Valor',
            '05/09/2026;Mercado;-200,00',
            '2026-09-10;Salario;500,00',
            '2026-09-15;Tarifa;-50,00',
        ]);

        $this->actingAs($user)->post(route('reconciliations.store'), [
            'account_id' => $account->id,
            'period_start' => '2026-09-01',
            'statement_date' => '2026-09-30',
            'declared_balance' => '1250',
            'file' => UploadedFile::fake()->createWithContent('extrato.csv', $csv),
        ])->assertRedirect();

        $reconciliation = Reconciliation::query()->sole();
        $this->assertSame(['total' => 3, 'invalid' => 0, 'matched' => 2, 'missing' => 1, 'extras' => 0], $reconciliation->summary);
        $this->assertSame('-50.0000', $reconciliation->delta);

        $this->actingAs($user)->post(route('reconciliations.confirm', $reconciliation))->assertRedirect();

        $reconciliation->refresh();
        $this->assertSame(ReconciliationStatus::Divergent, $reconciliation->status);
        $this->assertSame('-50.0000', $reconciliation->delta);
    }

    public function test_app_transactions_without_declared_row_are_listed_as_extras(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create(['initial_balance' => '1000']);
        Transaction::factory()->for($user)->create(['account_id' => $account->id,
            'type' => TransactionType::Expense,
            'amount' => '200.00',
            'transaction_date' => '2026-09-05',
        ]);
        Transaction::factory()->for($user)->create(['account_id' => $account->id,
            'type' => TransactionType::Expense,
            'amount' => '90.00',
            'transaction_date' => '2026-09-12',
        ]);
        Transaction::factory()->for($user)->create(['account_id' => $account->id,
            'type' => TransactionType::Income,
            'amount' => '500.00',
            'transaction_date' => '2026-09-10',
        ]);
        $csv = "Data;Descricao;Valor\n2026-09-10;Salario;500,00";

        $this->actingAs($user)->post(route('reconciliations.store'), [
            'account_id' => $account->id,
            'period_start' => '2026-09-01',
            'statement_date' => '2026-09-30',
            'declared_balance' => '1500',
            'file' => UploadedFile::fake()->createWithContent('extrato.csv', $csv),
        ])->assertRedirect();

        $reconciliation = Reconciliation::query()->sole();
        $this->assertSame(['total' => 1, 'invalid' => 0, 'matched' => 1, 'missing' => 0, 'extras' => 2], $reconciliation->summary);

        $this->actingAs($user)
            ->get(route('reconciliations.show', $reconciliation))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('extras', 2));
    }

    public function test_user_cannot_view_or_confirm_another_users_reconciliation(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $account = FinancialAccount::factory()->for($owner)->create(['initial_balance' => '0']);
        $csv = "Data;Descricao;Valor\n2026-09-05;Teste;10,00";

        $this->actingAs($owner)->post(route('reconciliations.store'), [
            'account_id' => $account->id,
            'period_start' => '2026-09-01',
            'statement_date' => '2026-09-30',
            'declared_balance' => '10',
            'file' => UploadedFile::fake()->createWithContent('extrato.csv', $csv),
        ])->assertRedirect();

        $reconciliation = Reconciliation::query()->sole();

        $this->actingAs($intruder)->get(route('reconciliations.show', $reconciliation))->assertForbidden();
        $this->actingAs($intruder)->post(route('reconciliations.confirm', $reconciliation))->assertForbidden();
        $this->assertSame(ReconciliationStatus::Previewed, $reconciliation->refresh()->status);
    }

    public function test_store_requires_an_owned_account_and_valid_statement_headers(): void
    {
        $user = User::factory()->create();
        $intruder = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create();

        $this->actingAs($intruder)->post(route('reconciliations.store'), [
            'account_id' => $account->id,
            'period_start' => '2026-09-01',
            'statement_date' => '2026-09-30',
            'declared_balance' => '100',
            'file' => UploadedFile::fake()->createWithContent('extrato.csv', "Data;Descricao;Valor\n2026-09-05;Teste;10,00"),
        ])->assertSessionHasErrors('account_id');

        $this->actingAs($user)->post(route('reconciliations.store'), [
            'account_id' => $account->id,
            'period_start' => '2026-09-01',
            'statement_date' => '2026-09-30',
            'declared_balance' => '100',
            'file' => UploadedFile::fake()->createWithContent('extrato.csv', "Data;Descricao\n2026-09-05;Teste"),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('reconciliations', 0);
    }
}
