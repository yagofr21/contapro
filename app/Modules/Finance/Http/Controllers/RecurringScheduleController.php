<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Actions\CreateTransactionSchedule;
use App\Modules\Finance\Actions\DeleteTransactionSchedule;
use App\Modules\Finance\Actions\UpdateTransactionSchedule;
use App\Modules\Finance\Enums\ScheduleFrequency;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Http\Requests\TransactionScheduleRequest;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\TransactionSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecurringScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TransactionSchedule::class);

        $schedules = $request->user()->transactionSchedules()
            ->with(['account:id,name', 'category:id,name,color'])
            ->latest('id')
            ->get()
            ->map(fn (TransactionSchedule $schedule): array => [
                'id' => $schedule->id,
                'account_id' => $schedule->account_id,
                'account_name' => $schedule->account->name,
                'category_id' => $schedule->category_id,
                'category_name' => $schedule->category?->name,
                'category_color' => $schedule->category?->color,
                'type' => $schedule->type->value,
                'amount' => $schedule->amount,
                'frequency' => $schedule->frequency->value,
                'frequency_label' => $schedule->frequency->label(),
                'starts_on' => $schedule->starts_on->format('Y-m-d'),
                'ends_on' => $schedule->ends_on?->format('Y-m-d'),
                'next_run_date' => $schedule->next_run_date->format('Y-m-d'),
                'description' => $schedule->description,
                'is_active' => $schedule->is_active,
            ]);

        return Inertia::render('Recurring/Index', [
            'schedules' => $schedules,
            ...$this->formOptions($request),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', TransactionSchedule::class);

        return Inertia::render('Recurring/Create', $this->formOptions($request));
    }

    public function store(TransactionScheduleRequest $request, CreateTransactionSchedule $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return to_route('recurring.index')->with('success', 'Recorrencia criada com sucesso.');
    }

    public function edit(Request $request, TransactionSchedule $recurring): Response
    {
        $this->authorize('update', $recurring);

        return Inertia::render('Recurring/Edit', [
            ...$this->formOptions($request),
            'schedule' => [
                'id' => $recurring->id,
                'type' => $recurring->type->value,
                'account_id' => $recurring->account_id,
                'destination_account_id' => $recurring->destination_account_id,
                'category_id' => $recurring->category_id,
                'amount' => $recurring->amount,
                'frequency' => $recurring->frequency->value,
                'starts_on' => $recurring->starts_on->format('Y-m-d'),
                'ends_on' => $recurring->ends_on?->format('Y-m-d'),
                'description' => $recurring->description,
                'is_active' => $recurring->is_active,
            ],
        ]);
    }

    public function update(TransactionScheduleRequest $request, TransactionSchedule $recurring, UpdateTransactionSchedule $action): RedirectResponse
    {
        $action->handle($recurring, $request->validated());

        return to_route('recurring.index')->with('success', 'Recorrencia atualizada com sucesso.');
    }

    public function destroy(TransactionSchedule $recurring, DeleteTransactionSchedule $action): RedirectResponse
    {
        $this->authorize('delete', $recurring);
        $action->handle($recurring);

        return to_route('recurring.index')->with('success', 'Recorrencia removida com sucesso.');
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
            'frequencies' => collect(ScheduleFrequency::supported())
                ->map(fn (ScheduleFrequency $frequency): array => [
                    'value' => $frequency->value,
                    'label' => $frequency->label(),
                ]),
            'types' => [
                ['value' => TransactionType::Expense->value, 'label' => 'Despesa'],
                ['value' => TransactionType::Income->value, 'label' => 'Receita'],
                ['value' => 'transfer', 'label' => 'Transferencia'],
            ],
        ];
    }
}
