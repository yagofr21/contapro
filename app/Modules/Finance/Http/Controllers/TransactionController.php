<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Finance\Actions\CreateInstallment;
use App\Modules\Finance\Actions\CreateTransaction;
use App\Modules\Finance\Actions\DeleteTransaction;
use App\Modules\Finance\Actions\ProcessInstallments;
use App\Modules\Finance\Actions\UpdateTransaction;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Http\Requests\TransactionRequest;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Support\InstallmentMath;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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

        $this->hydrateDestinations($request->user(), $transactions);

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

    public function store(TransactionRequest $request, CreateTransaction $action, CreateInstallment $createInstallment, ProcessInstallments $processInstallments): RedirectResponse
    {
        if ($this->isInstallmentPurchase($request)) {
            $totalAmount = $request->string('amount')->toString();
            $totalCount = (int) $request->input('total_count');

            $createInstallment->handle($request->user(), [
                'type' => 'expense',
                'account_id' => $request->integer('account_id'),
                'category_id' => $request->integer('category_id') !== 0 ? $request->integer('category_id') : null,
                'total_amount' => $totalAmount,
                'total_count' => $totalCount,
                'starts_on' => $request->string('first_installment_date')->toString(),
                'description' => $request->input('description'),
            ]);

            $parcela = InstallmentMath::centsToAmount(
                InstallmentMath::split(InstallmentMath::amountToCents($totalAmount), $totalCount)['base'],
            );

            $processInstallments->handle();

            $success = 'Compra parcelada lancada com sucesso.';
            $detail = sprintf(
                '%s em %d parcelas de R$ %s realizadas no cartao.',
                $this->formatCurrency($totalAmount),
                $totalCount,
                $this->formatCurrency($parcela),
            );
        } else {
            $created = $action->handle($request->user(), $request->validated());

            [$success, $detail] = match (true) {
                $created->transfer_id !== null => [
                    'Transferencia realizada com sucesso.',
                    sprintf('R$ %s movimentados entre contas.', $this->formatCurrency((string) $created->amount)),
                ],
                $created->type === TransactionType::Income => [
                    'Receita lancada com sucesso.',
                    sprintf('R$ %s adicionados a conta.', $this->formatCurrency((string) $created->amount)),
                ],
                default => [
                    'Despesa lancada com sucesso.',
                    sprintf('R$ %s registrados na conta.', $this->formatCurrency((string) $created->amount)),
                ],
            };
        }

        return $request->boolean('from_dashboard')
            ? to_route('dashboard')->with('success', $success)->with('detail', $detail)
            : to_route('transactions.index')->with('success', $success)->with('detail', $detail);
    }

    public function edit(Request $request, Transaction $transaction): Response
    {
        $this->authorize('update', $transaction);

        return Inertia::render('Transactions/Edit', [
            ...$this->formOptions($request),
            'transaction' => $this->serialize($transaction),
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
                ->get(['id', 'name', 'currency', 'type'])
                ->map(fn (FinancialAccount $account): array => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'currency' => $account->currency->value,
                    'type' => $account->type->value,
                ]),
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
        $destinationAccountId = null;
        if ($transaction->transfer_id !== null) {
            $destinationAccountId = Transaction::query()
                ->where('user_id', $transaction->user_id)
                ->where('transfer_id', $transaction->transfer_id)
                ->where('type', TransactionType::TransferIn->value)
                ->value('account_id');
        }

        return [
            'id' => $transaction->id,
            'account_id' => $transaction->account_id,
            'account_name' => $transaction->account->name,
            'currency' => $transaction->account->currency->value,
            'category_id' => $transaction->category_id,
            'category_name' => $transaction->category?->name,
            'category_color' => $transaction->category?->color,
            'type' => $transaction->transfer_id === null ? $transaction->type->value : 'transfer',
            'amount' => $transaction->amount,
            'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
            'description' => $transaction->description,
            'is_transfer' => $transaction->transfer_id !== null,
            'transfer_id' => $transaction->transfer_id,
            'destination_account_id' => $destinationAccountId,
        ];
    }

    /** @param  LengthAwarePaginator<int, array<string, mixed>>  $paginator
     */
    private function hydrateDestinations(User $user, LengthAwarePaginator $paginator): void
    {
        $transferIds = collect($paginator->items())
            ->where('is_transfer', true)
            ->whereNotNull('transfer_id')
            ->pluck('transfer_id')
            ->all();

        if ($transferIds === []) {
            return;
        }

        $destinations = Transaction::query()
            ->where('user_id', $user->id)
            ->whereIn('transfer_id', $transferIds)
            ->where('type', TransactionType::TransferIn->value)
            ->pluck('account_id', 'transfer_id')
            ->all();

        $items = $paginator->getCollection()->map(function (array $item) use ($destinations): array {
            $item['destination_account_id'] = $item['transfer_id'] !== null
                ? ($destinations[$item['transfer_id']] ?? null)
                : null;

            return $item;
        });

        /** @var Collection<int, array<string, mixed>> $items */
        $paginator->setCollection($items);
    }

    private function isInstallmentPurchase(TransactionRequest $request): bool
    {
        return $request->boolean('install_in') && $request->string('type')->toString() === 'expense';
    }

    private function formatCurrency(string $amount): string
    {
        return number_format((float) $amount, 2, ',', '.');
    }
}
