<?php

namespace Tests\Feature\ImportExport;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_export_is_filtered_ordered_and_safe_for_spreadsheets(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::factory()->for($user)->create(['name' => 'Conta principal', 'currency' => 'BRL']);
        Transaction::factory()->for($user)->for($account, 'account')->create([
            'type' => TransactionType::Income,
            'amount' => '100.5000',
            'description' => '=FORMULA()',
            'transaction_date' => '2026-01-01',
        ]);
        Transaction::factory()->for($user)->for($account, 'account')->create([
            'type' => TransactionType::TransferIn,
            'amount' => '50',
            'description' => 'Entrada duplicada',
            'transaction_date' => '2026-01-02',
        ]);
        Transaction::factory()->for($user)->for($account, 'account')->create([
            'type' => TransactionType::TransferOut,
            'amount' => '50',
            'description' => 'Transferencia unica',
            'transaction_date' => '2026-01-02',
        ]);
        $other = User::factory()->create();
        Transaction::factory()->for($other)->create([
            'description' => 'Registro de terceiro',
            'transaction_date' => '2026-01-01',
        ]);

        $response = $this->actingAs($user)->get(route('reports.transactions.export', [
            'from' => '2026-01-01',
            'to' => '2026-01-31',
            'currency' => 'BRL',
        ]));

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload('transacoes-2026-01-01-a-2026-01-31-BRL.csv');
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBFData;Tipo;Descricao;Conta;Categoria;Valor;Moeda\r\n", $csv);
        $this->assertStringContainsString("2026-01-01;Receita;'=FORMULA();\"Conta principal\";;100.5000;BRL", $csv);
        $this->assertStringContainsString('Transferencia unica', $csv);
        $this->assertStringNotContainsString('Entrada duplicada', $csv);
        $this->assertStringNotContainsString('Registro de terceiro', $csv);
    }
}
