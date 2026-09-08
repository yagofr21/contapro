<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Finance\Actions\ReceiveExpectedIncome;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Http\Requests\ExpectedIncomeRequest;
use App\Modules\Finance\Models\ExpectedIncome;
use App\Modules\Finance\Queries\ExpectedIncomeQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpectedIncomeController extends Controller
{
    public function index(Request $request, ExpectedIncomeQuery $query): Response
    {
        $this->authorize('viewAny', ExpectedIncome::class);
        $user = $request->user();

        return Inertia::render('ExpectedIncomes/Index', [
            'expectedIncomes' => [
                ...$query->pendingFor($user),
                ...$query->recentFor($user),
            ],
            ...$this->formOptions($user),
        ]);
    }

    public function store(ExpectedIncomeRequest $request): RedirectResponse
    {
        $request->user()->expectedIncomes()->create($request->validated());

        return back()->with('success', 'Receita futura cadastrada.');
    }

    public function receive(Request $request, ExpectedIncome $expectedIncome, ReceiveExpectedIncome $action): RedirectResponse
    {
        $this->authorize('receive', $expectedIncome);
        $action->handle($expectedIncome);

        return back()->with('success', 'Recebimento registrado como lancamento no fluxo de caixa.');
    }

    public function destroy(Request $request, ExpectedIncome $expectedIncome): RedirectResponse
    {
        $this->authorize('delete', $expectedIncome);

        if ($expectedIncome->received) {
            return back()->withErrors(['expected_income' => 'Uma receita ja recebida nao pode ser removida.']);
        }

        $expectedIncome->delete();

        return back()->with('success', 'Receita futura removida.');
    }

    /** @return array<string, mixed> */
    private function formOptions(User $user): array
    {
        return [
            'accounts' => $user->financialAccounts()
                ->where('is_archived', false)
                ->orderBy('name')
                ->get(['id', 'name', 'currency']),
            'categories' => $user->categories()
                ->where('type', CategoryType::Income->value)
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'color']),
        ];
    }
}
