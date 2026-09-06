<?php

namespace Tests\Feature\ImportExport;

use App\Models\User;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\ImportExport\Enums\ImportStatus;
use App\Modules\ImportExport\Models\ImportBatch;
use App\Modules\Investment\Enums\AssetTransactionType;
use App\Modules\Investment\Models\Asset;
use App\Modules\Investment\Models\AssetTransaction;
use App\Modules\Investment\Models\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CsvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_csv_is_previewed_and_confirmed_atomically(): void
    {
        $user = User::factory()->create();
        $source = FinancialAccount::factory()->for($user)->create(['name' => 'Principal', 'currency' => 'BRL']);
        $destination = FinancialAccount::factory()->for($user)->create(['name' => 'Reserva', 'currency' => 'BRL']);
        Category::factory()->for($user)->create(['name' => 'Mercado', 'type' => CategoryType::Expense]);
        $csv = implode("\r\n", [
            'Data;Tipo;Descricao;Conta;Conta destino;Categoria;Valor;Moeda',
            '05/09/2026;Despesa;Compras;Principal;;Mercado;1.234,56;BRL',
            '2026-09-06;Transferencia;Reserva mensal;Principal;Reserva;;500,00;BRL',
        ]);

        $response = $this->actingAs($user)->post(route('imports.store'), [
            'kind' => 'financial',
            'file' => UploadedFile::fake()->createWithContent('financeiro.csv', $csv),
        ]);

        $batch = ImportBatch::query()->sole();
        $response->assertRedirect(route('imports.show', $batch));
        $this->assertSame(['total' => 2, 'valid' => 2, 'invalid' => 0, 'duplicate' => 0, 'imported' => 0, 'delimiter' => ';'], $batch->summary);
        $this->assertDatabaseCount('transactions', 0);

        $this->actingAs($user)->post(route('imports.confirm', $batch), [
            'skip_invalid' => false,
        ])->assertRedirect(route('imports.show', $batch));

        $this->assertDatabaseCount('transactions', 3);
        $this->assertDatabaseHas('transactions', [
            'account_id' => $source->id,
            'type' => TransactionType::Expense->value,
            'amount' => '1234.5600',
        ]);
        $this->assertDatabaseHas('transactions', [
            'account_id' => $destination->id,
            'type' => TransactionType::TransferIn->value,
            'amount' => '500.0000',
        ]);
        $this->assertSame(ImportStatus::Confirmed, $batch->refresh()->status);
    }

    public function test_invalid_rows_require_explicit_skip_and_duplicate_rows_are_not_imported(): void
    {
        $user = User::factory()->create();
        FinancialAccount::factory()->for($user)->create(['name' => 'Principal', 'currency' => 'BRL']);
        $validCsv = "Data;Tipo;Descricao;Conta;Conta destino;Categoria;Valor;Moeda\n2026-09-05;Receita;Salario;Principal;;;1000,00;BRL";
        $first = $this->upload($user, 'financial', $validCsv);
        $this->actingAs($user)->post(route('imports.confirm', $first), ['skip_invalid' => false])->assertRedirect();

        $duplicate = $this->upload($user, 'financial', $validCsv);
        $this->assertSame(1, $duplicate->summary['duplicate']);
        $this->assertDatabaseCount('transactions', 1);

        $mixedCsv = "Data;Tipo;Descricao;Conta;Conta destino;Categoria;Valor;Moeda\n2026-09-06;Receita;Extra;Principal;;;10,00;BRL\n31/02/2026;Receita;Data ruim;Principal;;;20,00;BRL";
        $mixed = $this->upload($user, 'financial', $mixedCsv);
        $this->actingAs($user)->post(route('imports.confirm', $mixed), ['skip_invalid' => false])
            ->assertSessionHasErrors('skip_invalid');
        $this->assertDatabaseCount('transactions', 1);

        $this->actingAs($user)->post(route('imports.confirm', $mixed), ['skip_invalid' => true])->assertRedirect();
        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_investment_rows_are_confirmed_in_chronological_order(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create(['currency' => 'BRL']);
        Asset::factory()->create(['symbol' => 'PETR4', 'market' => 'B3', 'currency' => 'BRL']);
        $csv = implode("\n", [
            'Data;Tipo;Ativo;Mercado;Corretora;Quantidade;Preco unitario;Taxas;Valor bruto;Valor liquido;Proporcao origem;Proporcao destino;Observacao',
            '2026-01-02;Venda;PETR4;B3;;5;20;0;;;;;Venda',
            '2026-01-01;Compra;PETR4;B3;;10;10;0;;;;;Compra',
        ]);
        $batch = $this->upload($user, 'investment', $csv, $portfolio);

        $this->actingAs($user)->post(route('imports.confirm', $batch), ['skip_invalid' => false])->assertRedirect();

        $transactions = AssetTransaction::query()->orderBy('transaction_date')->get();
        $this->assertCount(2, $transactions);
        $this->assertSame(AssetTransactionType::Buy, $transactions[0]->type);
        $this->assertSame('50.0000', $transactions[1]->realized_profit_loss);
        $this->assertDatabaseHas('portfolio_holdings', ['portfolio_id' => $portfolio->id, 'quantity' => 5]);
    }

    public function test_user_cannot_view_or_confirm_another_users_import(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        FinancialAccount::factory()->for($owner)->create(['name' => 'Principal']);
        $batch = $this->upload($owner, 'financial', "Data;Tipo;Descricao;Conta;Conta destino;Categoria;Valor;Moeda\n2026-09-05;Receita;Teste;Principal;;;10;BRL");

        $this->actingAs($intruder)->get(route('imports.show', $batch))->assertForbidden();
        $this->actingAs($intruder)->post(route('imports.confirm', $batch), ['skip_invalid' => false])->assertForbidden();
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_investment_export_matches_the_import_format_and_is_private(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $asset = Asset::factory()->create(['symbol' => 'PETR4', 'market' => 'B3']);
        $portfolio->transactions()->create([
            'asset_id' => $asset->id,
            'type' => AssetTransactionType::Buy,
            'quantity' => '10',
            'unit_price' => '25.5',
            'fees' => '1.5',
            'transaction_date' => '2026-09-06',
            'note' => '=observacao',
        ]);

        $response = $this->actingAs($user)->get(route('portfolios.operations.export', $portfolio));

        $response->assertOk()->assertDownload("operacoes-{$portfolio->id}.csv");
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBFData;Tipo;Ativo;Mercado;Corretora;Quantidade;\"Preco unitario\";Taxas;\"Valor bruto\";\"Valor liquido\";\"Proporcao origem\";\"Proporcao destino\";Observacao", $csv);
        $this->assertStringContainsString("2026-09-06;Compra;PETR4;B3;;10.00000000;25.50000000;1.5000;;;;;'=observacao", $csv);

        $this->actingAs(User::factory()->create())
            ->get(route('portfolios.operations.export', $portfolio))
            ->assertForbidden();
    }

    private function upload(User $user, string $kind, string $csv, ?Portfolio $portfolio = null): ImportBatch
    {
        $this->actingAs($user)->post(route('imports.store'), [
            'kind' => $kind,
            'portfolio_id' => $portfolio?->id,
            'file' => UploadedFile::fake()->createWithContent('dados.csv', $csv),
        ])->assertRedirect();

        return ImportBatch::query()->latest('id')->firstOrFail();
    }
}
