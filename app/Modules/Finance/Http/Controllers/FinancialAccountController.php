<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Actions\CreateFinancialAccount;
use App\Modules\Finance\Actions\CreateTransaction;
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

    public function show(Request $request, FinancialAccount $account): Response
    {
        $this->authorize('view', $account);

        abort_unless($account->type === FinancialAccountType::CreditCard, 404);

        return Inertia::render('Accounts/Show', [
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
                'balance' => (new AccountSummaryQuery)->forUser($request->user())->firstWhere('id', $account->id)['balance'] ?? '0.0000',
                'credit_card' => (new CreditCardSummaryQuery)->forAccount($account),
                'is_archived' => $account->is_archived,
            ],
            'paymentAccounts' => $request->user()->financialAccounts()
                ->where('is_archived', false)
                ->where('type', '!=', FinancialAccountType::CreditCard->value)
                ->where('currency', $account->currency->value)
                ->orderBy('name')
                ->get(['id', 'name', 'currency'])
                ->map(fn (FinancialAccount $paymentAccount): array => [
                    'id' => $paymentAccount->id,
                    'name' => $paymentAccount->name,
                    'currency' => $paymentAccount->currency->value,
                ]),
            'banks' => (new BankOptionsQuery)->active(),
        ]);
    }

    public function store(FinancialAccountRequest $request, CreateFinancialAccount $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return to_route('accounts.index')->with('success', 'Conta criada com sucesso.');
    }

    public function payCard(Request $request, FinancialAccount $account, CreateTransaction $createTransaction): RedirectResponse
    {
        $this->authorize('view', $account);
        abort_unless($account->type === FinancialAccountType::CreditCard, 404);

        $validated = $request->validate([
            'account_id' => ['required', 'integer'],
            'amount' => ['required', 'decimal:0,4', 'gt:0', 'max:999999999999999.9999'],
            'transaction_date' => ['required', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $source = FinancialAccount::query()
            ->whereBelongsTo($request->user())
            ->where('is_archived', false)
            ->findOrFail($validated['account_id']);

        abort_if($source->id === $account->id || $source->currency !== $account->currency || $source->type === FinancialAccountType::CreditCard, 422);

        $createTransaction->handle($request->user(), [
            'type' => 'transfer',
            'account_id' => $source->id,
            'destination_account_id' => $account->id,
            'amount' => $validated['amount'],
            'transaction_date' => $validated['transaction_date'],
            'description' => $validated['description'] ?? 'Pagamento de fatura',
        ]);

        return back()->with('success', 'Pagamento de fatura registrado com sucesso.');
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
                    FinancialAccountType::CreditCard => 'Cartão de crédito',
                    FinancialAccountType::Investment => 'Investimentos',
                    FinancialAccountType::Savings => 'Poupança',
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
