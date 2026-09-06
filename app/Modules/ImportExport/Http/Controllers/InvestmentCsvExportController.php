<?php

namespace App\Modules\ImportExport\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Investment\Enums\AssetTransactionType;
use App\Modules\Investment\Models\AssetTransaction;
use App\Modules\Investment\Models\Portfolio;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvestmentCsvExportController extends Controller
{
    public function __invoke(Portfolio $portfolio): StreamedResponse
    {
        $this->authorize('view', $portfolio);
        $transactions = $portfolio->transactions()
            ->with(['asset:id,symbol,market', 'broker:id,name'])
            ->orderBy('transaction_date')
            ->orderBy('id');

        return response()->streamDownload(function () use ($transactions): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                return;
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'Data', 'Tipo', 'Ativo', 'Mercado', 'Corretora', 'Quantidade',
                'Preco unitario', 'Taxas', 'Valor bruto', 'Valor liquido',
                'Proporcao origem', 'Proporcao destino', 'Observacao',
            ], ';', '"', '', "\r\n");

            foreach ($transactions->cursor() as $transaction) {
                fputcsv($stream, [
                    $transaction->transaction_date->format('Y-m-d'),
                    $this->typeLabel($transaction),
                    $this->safeCell($transaction->asset->symbol),
                    $transaction->asset->market->value,
                    $this->safeCell($transaction->broker_id !== null ? $transaction->broker->name : ''),
                    $transaction->type === AssetTransactionType::Buy || $transaction->type === AssetTransactionType::Sell ? $transaction->quantity : '',
                    $transaction->type === AssetTransactionType::Buy || $transaction->type === AssetTransactionType::Sell ? $transaction->unit_price : '',
                    $transaction->type === AssetTransactionType::Buy || $transaction->type === AssetTransactionType::Sell ? $transaction->fees : '',
                    $transaction->gross_amount ?? '',
                    $transaction->net_amount ?? '',
                    $transaction->split_from ?? '',
                    $transaction->split_to ?? '',
                    $this->safeCell($transaction->note ?? ''),
                ], ';', '"', '', "\r\n");
            }

            fclose($stream);
        }, "operacoes-{$portfolio->id}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function typeLabel(AssetTransaction $transaction): string
    {
        return match ($transaction->type) {
            AssetTransactionType::Buy => 'Compra',
            AssetTransactionType::Sell => 'Venda',
            AssetTransactionType::Dividend => 'Dividendo',
            AssetTransactionType::Interest => 'Juros',
            AssetTransactionType::Split => 'Desdobramento',
        };
    }

    private function safeCell(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) === 1 ? "'{$value}" : $value;
    }
}
