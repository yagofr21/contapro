<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Actions\CreateTransaction;
use App\Modules\Finance\Actions\DeleteTransaction;
use App\Modules\Finance\Actions\UpdateTransaction;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Http\Requests\TransactionRequest;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Transaction::class);

        $filters = $request->validate([
            'account_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'in:income,expense,transfer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $transactions = $request->user()->transactions()
            ->with(['account:id,name,currency', 'category:id,name,color'])
            ->where('type', '!=', TransactionType::TransferIn->value)
            ->when($filters['account_id'] ?? null, fn (Builder $query, $id) => $query->where('account_id', $id))
            ->when($filters['category_id'] ?? null, fn (Builder $query, $id) => $query->where('category_id', $id))
            ->when($filters['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('transaction_date', '>=', $date))
            ->when($filters['to'] ?? null, fn (Builder $query, $date) => $query->whereDate('transaction_date', '<=', $date))
            ->when($filters['search'] ?? null, fn (Builder $query, $search) => $query->whereRaw(
                'LOWER(description) LIKE ?',
                ['%'.mb_strtolower((string) $search).'%'],
            ))
            ->when(($filters['type'] ?? null) === 'transfer', fn (Builder $query) => $query->where('type', TransactionType::TransferOut->value))
            ->when(in_array($filters['type'] ?? null, ['income', 'expense'], true), fn (Builder $query) => $query->where('type', $filters['type']))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Transaction $transaction) => $this->serialize($transaction));

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'filters' => $filters,
            ...$this->formOptions($request),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Transaction::class);

        return Inertia::render('Transactions/Create', $this->formOptions($request));
    }

    public function store(TransactionRequest $request, CreateTransaction $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return to_route('transactions.index')->with('success', 'Lancamento criado com sucesso.');
    }

    public function edit(Request $request, Transaction $transaction): Response
    {
        $this->authorize('update', $transaction);

        $destinationAccountId = null;
        if ($transaction->transfer_id !== null) {
            $destinationAccountId = Transaction::query()
                ->where('user_id', $request->user()->id)
                ->where('transfer_id', $transaction->transfer_id)
                ->where('type', TransactionType::TransferIn->value)
                ->value('account_id');
        }

        return Inertia::render('Transactions/Edit', [
            ...$this->formOptions($request),
            'transaction' => [
                ...$this->serialize($transaction),
                'type' => $transaction->transfer_id === null ? $transaction->type->value : 'transfer',
                'destination_account_id' => $destinationAccountId,
            ],
        ]);
    }

    public function update(TransactionRequest $request, Transaction $transaction, UpdateTransaction $action): RedirectResponse
    {
        $action->handle($transaction, $request->validated());

        return to_route('transactions.index')->with('success', 'Lancamento atualizado com sucesso.');
    }

    public function destroy(Transaction $transaction, DeleteTransaction $action): RedirectResponse
    {
        $this->authorize('delete', $transaction);
        $action->handle($transaction);

        return to_route('transactions.index')->with('success', 'Lancamento removido com sucesso.');
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
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type->value,
                    'color' => $category->color,
                ]),
        ];
    }

    /** @return array<string, mixed> */
    private function serialize(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'account_id' => $transaction->account_id,
            'account_name' => $transaction->account->name,
            'currency' => $transaction->account->currency->value,
            'category_id' => $transaction->category_id,
            'category_name' => $transaction->category?->name,
            'category_color' => $transaction->category?->color,
            'type' => $transaction->type->value,
            'amount' => $transaction->amount,
            'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
            'description' => $transaction->description,
            'is_transfer' => $transaction->transfer_id !== null,
        ];
    }
}
