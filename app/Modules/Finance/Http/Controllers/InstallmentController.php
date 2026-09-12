<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Actions\CreateInstallment;
use App\Modules\Finance\Actions\DeleteInstallment;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Http\Requests\InstallmentRequest;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Installment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstallmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Installment::class);

        $installments = $request->user()->installments()
            ->with(['account:id,name', 'category:id,name,color'])
            ->latest('id')
            ->get()
            ->map(fn (Installment $installment): array => $this->serialize($installment));

        return Inertia::render('Installments/Index', [
            'installments' => $installments,
            ...$this->formOptions($request),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Installment::class);

        return Inertia::render('Installments/Create', $this->formOptions($request));
    }

    public function store(InstallmentRequest $request, CreateInstallment $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return to_route('installments.index')->with('success', 'Serie de parcelas criada com sucesso.');
    }

    public function destroy(Installment $installment, DeleteInstallment $action): RedirectResponse
    {
        $this->authorize('delete', $installment);
        $action->handle($installment);

        return to_route('installments.index')->with('success', 'Serie de parcelas removida com sucesso.');
    }

    /** @return array<string, mixed> */
    private function serialize(Installment $installment): array
    {
        return [
            'id' => $installment->id,
            'account_id' => $installment->account_id,
            'account_name' => $installment->account->name,
            'category_id' => $installment->category_id,
            'category_name' => $installment->category?->name,
            'category_color' => $installment->category?->color,
            'type' => $installment->type->value,
            'amount' => $installment->amount,
            'total_amount' => $installment->totalAmountValue(),
            'total_count' => $installment->total_count,
            'paid_count' => $installment->paidCount(),
            'remaining_count' => $installment->remaining_count,
            'current_parcela' => $installment->currentParcela(),
            'total_paid' => $installment->totalPaidValue(),
            'total_remaining' => $installment->totalRemainingValue(),
            'next_due_date' => $installment->next_due_date->format('Y-m-d'),
            'description' => $installment->description,
            'is_finished' => $installment->isFinished(),
            'schedule' => $installment->schedule(),
        ];
    }

    /** @return array<string, mixed> */
    private function formOptions(Request $request): array
    {
        return [
            'accounts' => $request->user()->financialAccounts()
                ->where('is_archived', false)
                ->orderBy('name')
                ->get(['id', 'name', 'currency']),
            'categories' => $request->user()->categories()
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'color'])
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type->value,
                    'color' => $category->color,
                ]),
            'types' => [
                ['value' => TransactionType::Expense->value, 'label' => 'Despesa'],
                ['value' => TransactionType::Income->value, 'label' => 'Receita'],
            ],
        ];
    }
}
