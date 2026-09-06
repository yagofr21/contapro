<?php

namespace App\Modules\ImportExport\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Http\Requests\ReportRequest;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Transaction;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionCsvExportController extends Controller
{
    public function __invoke(ReportRequest $request): StreamedResponse
    {
        $filters = $request->validated();
        $filename = sprintf('transacoes-%s-a-%s-%s.csv', $filters['from'], $filters['to'], $filters['currency']);
        $transactions = $request->user()->transactions()
            ->with([
                'account:id,name,currency',
                'category:id,name',
                'transferCounterpart.account:id,name,currency',
            ])
            ->whereHas('account', fn ($query) => $query->where('currency', $filters['currency']))
            ->whereDate('transaction_date', '>=', $filters['from'])
            ->whereDate('transaction_date', '<=', $filters['to'])
            ->where('type', '!=', TransactionType::TransferIn->value)
            ->orderBy('transaction_date')
            ->orderBy('id');

        return response()->streamDownload(function () use ($transactions): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                return;
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Data', 'Tipo', 'Descricao', 'Conta', 'Conta destino', 'Categoria', 'Valor', 'Moeda'], ';', '"', '', "\r\n");

            foreach ($transactions->cursor() as $transaction) {
                fputcsv($stream, [
                    $transaction->transaction_date->format('Y-m-d'),
                    $this->typeLabel($transaction),
                    $this->safeCell($transaction->description ?? ''),
                    $this->safeCell($transaction->account->name),
                    $this->safeCell($transaction->transferCounterpart?->account->name ?? ''),
                    $this->safeCell($transaction->category_id !== null ? $transaction->category->name : ''),
                    $transaction->amount,
                    $transaction->account->currency->value,
                ], ';', '"', '', "\r\n");
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function typeLabel(Transaction $transaction): string
    {
        return match ($transaction->type) {
            TransactionType::Income => 'Receita',
            TransactionType::Expense => 'Despesa',
            TransactionType::TransferOut => 'Transferencia',
            TransactionType::TransferIn => 'Transferencia recebida',
        };
    }

    private function safeCell(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) === 1 ? "'{$value}" : $value;
    }
}
