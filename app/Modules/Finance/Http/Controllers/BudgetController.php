<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Actions\CreateBudget;
use App\Modules\Finance\Actions\UpdateBudget;
use App\Modules\Finance\Enums\BudgetPeriod;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Http\Requests\BudgetRequest;
use App\Modules\Finance\Models\Budget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Budget::class);

        $budgets = $request->user()->budgets()
            ->with('category:id,name,color')
            ->latest('starts_on')
            ->get()
            ->map(function (Budget $budget) use ($request): array {
                $endsOn = match ($budget->period) {
                    BudgetPeriod::Monthly => $budget->starts_on->copy()->endOfMonth(),
                    BudgetPeriod::Yearly => $budget->starts_on->copy()->endOfYear(),
                    BudgetPeriod::Custom => $budget->ends_on ?? $budget->starts_on,
                };
                $spent = (string) $request->user()->transactions()
                    ->where('type', TransactionType::Expense->value)
                    ->where('category_id', $budget->category_id)
                    ->whereBetween('transaction_date', [$budget->starts_on, $endsOn])
                    ->sum('amount');

                return [
                    'id' => $budget->id,
                    'category_id' => $budget->category_id,
                    'category_name' => $budget->category->name,
                    'category_color' => $budget->category->color,
                    'limit_amount' => $budget->limit_amount,
                    'spent' => bcadd($spent, '0', 4),
                    'period' => $budget->period->value,
                    'starts_on' => $budget->starts_on->format('Y-m-d'),
                    'ends_on' => $budget->ends_on?->format('Y-m-d'),
                ];
            });

        return Inertia::render('Budgets/Index', ['budgets' => $budgets]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Budget::class);

        return Inertia::render('Budgets/Create', $this->formOptions($request));
    }

    public function store(BudgetRequest $request, CreateBudget $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return to_route('budgets.index')->with('success', 'Orcamento criado com sucesso.');
    }

    public function edit(Request $request, Budget $budget): Response
    {
        $this->authorize('update', $budget);

        return Inertia::render('Budgets/Edit', [
            ...$this->formOptions($request),
            'budget' => [
                'id' => $budget->id,
                'category_id' => $budget->category_id,
                'limit_amount' => $budget->limit_amount,
                'period' => $budget->period->value,
                'starts_on' => $budget->starts_on->format('Y-m-d'),
                'ends_on' => $budget->ends_on?->format('Y-m-d'),
            ],
        ]);
    }

    public function update(BudgetRequest $request, Budget $budget, UpdateBudget $action): RedirectResponse
    {
        $action->handle($budget, $request->validated());

        return to_route('budgets.index')->with('success', 'Orcamento atualizado com sucesso.');
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        $this->authorize('delete', $budget);
        $budget->delete();

        return to_route('budgets.index')->with('success', 'Orcamento removido com sucesso.');
    }

    /** @return array<string, mixed> */
    private function formOptions(Request $request): array
    {
        return [
            'categories' => $request->user()->categories()
                ->where('type', CategoryType::Expense->value)
                ->orderBy('name')
                ->get(['id', 'name', 'color']),
            'periods' => [
                ['value' => BudgetPeriod::Monthly->value, 'label' => 'Mensal'],
                ['value' => BudgetPeriod::Yearly->value, 'label' => 'Anual'],
                ['value' => BudgetPeriod::Custom->value, 'label' => 'Personalizado'],
            ],
        ];
    }
}
