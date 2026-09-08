<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Actions\CreateFinancialGoal;
use App\Modules\Finance\Actions\UpdateFinancialGoal;
use App\Modules\Finance\Http\Requests\FinancialGoalRequest;
use App\Modules\Finance\Models\FinancialGoal;
use App\Modules\Finance\Queries\FinancialGoalQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancialGoalController extends Controller
{
    public function index(Request $request, FinancialGoalQuery $query): Response
    {
        $this->authorize('viewAny', FinancialGoal::class);

        return Inertia::render('Goals/Index', [
            'goals' => $query->forUser($request->user()),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', FinancialGoal::class);

        return Inertia::render('Goals/Create', $this->formOptions($request));
    }

    public function store(FinancialGoalRequest $request, CreateFinancialGoal $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return to_route('goals.index')->with('success', 'Meta criada com sucesso.');
    }

    public function edit(Request $request, FinancialGoal $goal): Response
    {
        $this->authorize('update', $goal);

        return Inertia::render('Goals/Edit', [
            ...$this->formOptions($request),
            'goal' => [
                'id' => $goal->id,
                'name' => $goal->name,
                'description' => $goal->description,
                'target_amount' => $goal->target_amount,
                'currency' => $goal->currency->value,
                'account_id' => $goal->account_id,
                'target_date' => $goal->target_date->format('Y-m-d'),
            ],
        ]);
    }

    public function update(FinancialGoalRequest $request, FinancialGoal $goal, UpdateFinancialGoal $action): RedirectResponse
    {
        $action->handle($goal, $request->validated());

        return to_route('goals.index')->with('success', 'Meta atualizada com sucesso.');
    }

    public function destroy(FinancialGoal $goal): RedirectResponse
    {
        $this->authorize('delete', $goal);
        $goal->delete();

        return to_route('goals.index')->with('success', 'Meta removida com sucesso.');
    }

    /** @return array<string, mixed> */
    private function formOptions(Request $request): array
    {
        return [
            'accounts' => $request->user()->financialAccounts()
                ->orderBy('name')
                ->get(['id', 'name', 'currency']),
            'currencies' => collect(Currency::cases())->map(fn ($currency) => [
                'value' => $currency->value,
                'label' => $currency->value,
            ]),
        ];
    }
}
