<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Actions\CreateFinancialAccount;
use App\Modules\Finance\Actions\UpdateFinancialAccount;
use App\Modules\Finance\Enums\FinancialAccountType;
use App\Modules\Finance\Http\Requests\FinancialAccountRequest;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Queries\AccountSummaryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancialAccountController extends Controller
{
    public function index(Request $request, AccountSummaryQuery $query): Response
    {
        $this->authorize('viewAny', FinancialAccount::class);

        return Inertia::render('Accounts/Index', [
            'accounts' => $query->forUser($request->user()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', FinancialAccount::class);

        return Inertia::render('Accounts/Create', $this->formOptions());
    }

    public function store(FinancialAccountRequest $request, CreateFinancialAccount $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return to_route('accounts.index')->with('success', 'Conta criada com sucesso.');
    }

    public function edit(FinancialAccount $account): Response
    {
        $this->authorize('update', $account);

        return Inertia::render('Accounts/Edit', [
            ...$this->formOptions(),
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type->value,
                'currency' => $account->currency->value,
                'initial_balance' => $account->initial_balance,
                'is_archived' => $account->is_archived,
            ],
        ]);
    }

    public function update(
        FinancialAccountRequest $request,
        FinancialAccount $account,
        UpdateFinancialAccount $action,
    ): RedirectResponse {
        $action->handle($account, $request->validated());

        return to_route('accounts.index')->with('success', 'Conta atualizada com sucesso.');
    }

    public function destroy(FinancialAccount $account): RedirectResponse
    {
        $this->authorize('delete', $account);
        $account->delete();

        return to_route('accounts.index')->with('success', 'Conta removida com sucesso.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'types' => collect(FinancialAccountType::cases())->map(fn ($type) => [
                'value' => $type->value,
                'label' => match ($type) {
                    FinancialAccountType::Cash => 'Dinheiro',
                    FinancialAccountType::Checking => 'Conta corrente',
                    FinancialAccountType::CreditCard => 'Cartao de credito',
                    FinancialAccountType::Investment => 'Investimentos',
                    FinancialAccountType::Savings => 'Poupanca',
                },
            ]),
            'currencies' => collect(Currency::cases())->map(fn ($currency) => [
                'value' => $currency->value,
                'label' => $currency->value,
            ]),
        ];
    }
}
