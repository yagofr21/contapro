<?php

namespace App\Modules\ImportExport\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ImportExport\Actions\ConfirmCsvImport;
use App\Modules\ImportExport\Actions\StoreCsvImport;
use App\Modules\ImportExport\Enums\ImportKind;
use App\Modules\ImportExport\Enums\ImportStatus;
use App\Modules\ImportExport\Http\Requests\ConfirmCsvImportRequest;
use App\Modules\ImportExport\Http\Requests\StoreCsvImportRequest;
use App\Modules\ImportExport\Models\ImportBatch;
use App\Modules\ImportExport\Models\ImportRow;
use App\Modules\Investment\Models\Portfolio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class CsvImportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ImportBatch::class);

        return $this->page($request);
    }

    public function store(StoreCsvImportRequest $request, StoreCsvImport $action): RedirectResponse
    {
        $validated = $request->validated();
        $portfolio = isset($validated['portfolio_id'])
            ? $request->user()->portfolios()->findOrFail($validated['portfolio_id'])
            : null;
        $file = $request->file('file');
        assert($file instanceof UploadedFile);
        $batch = $action->handle(
            $request->user(),
            ImportKind::from((string) $validated['kind']),
            $file,
            $portfolio,
        );

        return to_route('imports.show', $batch);
    }

    public function show(Request $request, ImportBatch $importBatch): Response
    {
        $this->authorize('view', $importBatch);

        return $this->page($request, $importBatch);
    }

    public function confirm(
        ConfirmCsvImportRequest $request,
        ImportBatch $importBatch,
        ConfirmCsvImport $action,
    ): RedirectResponse {
        $batch = $action->handle(
            $request->user(),
            $importBatch,
            $request->boolean('skip_invalid'),
        );

        return to_route('imports.show', $batch)->with('success', 'Importacao confirmada com sucesso.');
    }

    public function destroy(ImportBatch $importBatch): RedirectResponse
    {
        $this->authorize('delete', $importBatch);

        if ($importBatch->status === ImportStatus::Confirmed) {
            abort(422, 'Uma importacao confirmada nao pode ser removida.');
        }

        $importBatch->delete();

        return to_route('imports.index')->with('success', 'Previa de importacao removida.');
    }

    private function page(Request $request, ?ImportBatch $batch = null): Response
    {
        $user = $request->user();

        return Inertia::render('Imports/Index', [
            'batch' => $batch !== null ? [
                'id' => $batch->public_id,
                'kind' => $batch->kind->value,
                'status' => $batch->status->value,
                'filename' => $batch->original_filename,
                'portfolio_name' => $batch->portfolio?->name,
                'summary' => $batch->summary,
                'confirmed_at' => $batch->confirmed_at?->toIso8601String(),
            ] : null,
            'rows' => $batch?->rows()
                ->orderBy('row_number')
                ->limit(200)
                ->get()
                ->map(fn (ImportRow $row) => [
                    'id' => $row->id,
                    'row_number' => $row->row_number,
                    'raw' => $row->raw,
                    'normalized' => $row->normalized,
                    'status' => $row->status,
                    'errors' => $row->errors ?? [],
                ]) ?? [],
            'portfolios' => $user->portfolios()
                ->orderBy('name')
                ->get(['id', 'name', 'currency'])
                ->map(fn (Portfolio $portfolio) => [
                    'id' => $portfolio->id,
                    'name' => $portfolio->name,
                    'currency' => $portfolio->currency->value,
                ]),
            'history' => $user->importBatches()
                ->with('portfolio:id,name')
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn (ImportBatch $item) => [
                    'id' => $item->public_id,
                    'kind' => $item->kind->value,
                    'status' => $item->status->value,
                    'filename' => $item->original_filename,
                    'portfolio_name' => $item->portfolio?->name,
                    'summary' => $item->summary,
                    'created_at' => $item->created_at->toIso8601String(),
                ]),
        ]);
    }
}
