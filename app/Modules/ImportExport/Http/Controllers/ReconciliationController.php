<?php

namespace App\Modules\ImportExport\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use App\Modules\ImportExport\Actions\ConfirmReconciliation;
use App\Modules\ImportExport\Actions\StoreReconciliation;
use App\Modules\ImportExport\Enums\ReconciliationStatus;
use App\Modules\ImportExport\Http\Requests\ConfirmReconciliationRequest;
use App\Modules\ImportExport\Http\Requests\StoreReconciliationRequest;
use App\Modules\ImportExport\Models\Reconciliation;
use App\Modules\ImportExport\Models\ReconciliationRow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReconciliationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Reconciliation::class);

        return $this->page($request);
    }

    public function template(): StreamedResponse
    {
        $headers = ['Data', 'Descricao', 'Valor'];

        return response()->streamDownload(function () use ($headers): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                return;
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $headers, ';', '"', '', "\r\n");
            fclose($stream);
        }, 'modelo-extrato.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(StoreReconciliationRequest $request, StoreReconciliation $action): RedirectResponse
    {
        $validated = $request->validated();
        $account = $request->user()->financialAccounts()->findOrFail($validated['account_id']);
        $file = $request->file('file');
        assert($file instanceof UploadedFile);
        $reconciliation = $action->handle($request->user(), $account, $validated, $file);

        return to_route('reconciliations.show', $reconciliation)
            ->with('success', 'Previa de conciliacao gerada. Confira as regras antes de confirmar.');
    }

    public function show(Request $request, Reconciliation $reconciliation): Response
    {
        $this->authorize('view', $reconciliation);

        return $this->page($request, $reconciliation);
    }

    public function confirm(
        ConfirmReconciliationRequest $request,
        Reconciliation $reconciliation,
        ConfirmReconciliation $action,
    ): RedirectResponse {
        $reconciliation = $action->handle($request->user(), $reconciliation);

        return to_route('reconciliations.show', $reconciliation)->with('success', 'Conciliacao confirmada com sucesso.');
    }

    public function destroy(Reconciliation $reconciliation): RedirectResponse
    {
        $this->authorize('delete', $reconciliation);

        if ($reconciliation->status !== ReconciliationStatus::Previewed) {
            abort(422, 'Uma conciliacao confirmada nao pode ser removida.');
        }

        $reconciliation->delete();

        return to_route('reconciliations.index')->with('success', 'Previa de conciliacao removida.');
    }

    private function page(Request $request, ?Reconciliation $reconciliation = null): Response
    {
        $user = $request->user();

        if ($reconciliation === null) {
            return Inertia::render('Reconciliations/Index', [
                'accounts' => $user->financialAccounts()
                    ->orderBy('name')
                    ->get(['id', 'name', 'currency'])
                    ->map(fn (FinancialAccount $account) => [
                        'id' => $account->id,
                        'name' => $account->name,
                        'currency' => $account->currency->value,
                    ]),
                'history' => $user->reconciliations()
                    ->with('account:id,name')
                    ->latest()
                    ->limit(20)
                    ->get()
                    ->map(fn (Reconciliation $item) => [
                        'id' => $item->public_id,
                        'status' => $item->status->value,
                        'account_id' => $item->account_id,
                        'account_name' => $item->account?->name,
                        'currency' => $item->account?->currency->value ?? 'BRL',
                        'period_start' => $item->period_start->format('Y-m-d'),
                        'statement_date' => $item->statement_date->format('Y-m-d'),
                        'summary' => $item->summary,
                        'delta' => $this->stringify($item->delta),
                        'created_at' => $item->created_at->toIso8601String(),
                    ]),
            ]);
        }

        $matchedIds = $reconciliation->rows()
            ->whereNotNull('matched_transaction_id')
            ->pluck('matched_transaction_id');

        return Inertia::render('Reconciliations/Show', [
            'reconciliation' => [
                'id' => $reconciliation->public_id,
                'status' => $reconciliation->status->value,
                'account_id' => $reconciliation->account_id,
                'account_name' => $reconciliation->account?->name,
                'currency' => $reconciliation->account?->currency->value ?? 'BRL',
                'period_start' => $reconciliation->period_start->format('Y-m-d'),
                'statement_date' => $reconciliation->statement_date->format('Y-m-d'),
                'declared_balance' => $this->stringify($reconciliation->declared_balance),
                'detected_balance' => $this->stringify($reconciliation->detected_balance),
                'delta' => $this->stringify($reconciliation->delta),
                'summary' => $reconciliation->summary,
                'confirmed_at' => $reconciliation->confirmed_at?->toIso8601String(),
            ],
            'rows' => $reconciliation->rows()
                ->orderBy('row_number')
                ->limit(200)
                ->get()
                ->map(fn (ReconciliationRow $row) => [
                    'id' => $row->id,
                    'row_number' => $row->row_number,
                    'date' => $row->entry_date?->format('Y-m-d'),
                    'amount' => $this->stringify($row->amount),
                    'description' => $row->description,
                    'status' => $row->status,
                    'match_rule' => $row->match_rule,
                    'errors' => $row->errors ?? [],
                ]),
            'extras' => $user->transactions()
                ->where('account_id', $reconciliation->account_id)
                ->whereBetween('transaction_date', [
                    $reconciliation->period_start->format('Y-m-d'),
                    $reconciliation->statement_date->format('Y-m-d'),
                ])
                ->whereNotIn('id', $matchedIds)
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->limit(100)
                ->get()
                ->map(fn (Transaction $transaction) => [
                    'id' => $transaction->id,
                    'type' => $transaction->type->value,
                    'amount' => $this->stringify($transaction->amount),
                    'date' => $transaction->transaction_date->format('Y-m-d'),
                    'description' => $transaction->description,
                ]),
        ]);
    }

    private function stringify(mixed $value): string
    {
        return (string) $value;
    }
}
