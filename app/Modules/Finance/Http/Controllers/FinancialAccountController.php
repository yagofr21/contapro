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
use App\Modules\Finance\Queries\BankOptionsQuery;
use App\Modules\Finance\Queries\CreditCardSummaryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancialAccountController extends Controller
{
    public function index(Request $request, AccountSummaryQuery $query): Response
    {
        $this->authorize('viewAny', FinancialAccount::class);

        $creditCards = (new CreditCardSummaryQuery)->forUser($request->user());
        $accounts = $query->forUser($request->user())
            ->map(function (array $account) use ($creditCards): array {
                if (isset($creditCards[$account['id']])) {
                    $account['credit_card'] = $creditCards[$account['id']];
                }

                return $account;
            });

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
            ...$this->formOptions(),
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
                'bank' => $account->bank,
                'color' => $account->color,
                'type' => $account->type->value,
                'currency' => $account->currency->value,
                'initial_balance' => $account->initial_balance,
                'credit_limit' => $account->credit_limit,
                'credit_closing_day' => $account->credit_closing_day,
                'credit_due_day' => $account->credit_due_day,
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
            'banks' => (new BankOptionsQuery)->active(),
        ];
    }
}
