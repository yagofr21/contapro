<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Finance\Actions\CreateCategory;
use App\Modules\Finance\Actions\UpdateCategory;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Http\Requests\CategoryRequest;
use App\Modules\Finance\Models\Category;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Category::class);

        return Inertia::render('Categories/Index', [
            'categories' => $request->user()->categories()
                ->with('parent:id,name')
                ->orderBy('type')
                ->orderBy('name')
                ->get()
                ->map(fn (Category $category) => $this->serialize($category)),
            ...$this->formOptions($request),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Category::class);

        return Inertia::render('Categories/Create', $this->formOptions($request));
    }

    public function store(CategoryRequest $request, CreateCategory $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return to_route('categories.index')->with('success', 'Categoria criada com sucesso.');
    }

    public function show(Request $request, Category $category): Response
    {
        $this->authorize('view', $category);

        $month = $request->validate([
            'month' => ['nullable', 'string', 'regex:/^\\d{4}-\\d{2}$/'],
        ])['month'] ?? now()->format('Y-m');

        $start = CarbonImmutable::parse($month.'-01')->startOfMonth();
        $end = $start->endOfMonth();

        $daily = $this->dailyTotals($request->user(), $this->categoryScopeIds($category), $start, $end);

        return Inertia::render('Categories/Show', [
            'category' => $this->serialize($category),
            'month' => $start->format('Y-m'),
            'monthLabel' => $start->translatedFormat('F Y'),
            'calendar' => $this->calendarGrid($start, $daily['daily']),
            'summaries' => $this->summaries($daily),
            'heatCurrency' => $this->heatCurrency($daily),
            'prevMonth' => $start->subMonth()->format('Y-m'),
            'nextMonth' => $start->addMonth()->format('Y-m'),
        ]);
    }

    public function edit(Request $request, Category $category): Response
    {
        $this->authorize('update', $category);

        return Inertia::render('Categories/Edit', [
            ...$this->formOptions($request, $category),
            'category' => $this->serialize($category),
        ]);
    }

    public function update(CategoryRequest $request, Category $category, UpdateCategory $action): RedirectResponse
    {
        $action->handle($category, $request->validated());

        return to_route('categories.index')->with('success', 'Categoria atualizada com sucesso.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);
        $category->delete();

        return to_route('categories.index')->with('success', 'Categoria removida com sucesso.');
    }

    /** @return array<string, mixed> */
    private function formOptions(Request $request, ?Category $current = null): array
    {
        return [
            'types' => [
                ['value' => CategoryType::Expense->value, 'label' => 'Despesa'],
                ['value' => CategoryType::Income->value, 'label' => 'Receita'],
            ],
            'parentOptions' => $request->user()->categories()
                ->when($current, fn ($query) => $query->whereKeyNot($current->id))
                ->orderBy('name')
                ->get(['id', 'name', 'type'])
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type->value,
                ]),
        ];
    }

    /** @return array<string, mixed> */
    private function serialize(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'type' => $category->type->value,
            'color' => $category->color,
            'parent_id' => $category->parent_id,
            'parent_name' => $category->parent?->name,
        ];
    }

    /**
     * @param  list<int>  $categoryIds
     * @return array{
     *     daily: array<string, array<string, string>>,
     *     totals: array<string, string>,
     *     dates: array<string, array<string, string>>
     * }
     */
    private function dailyTotals(User $user, array $categoryIds, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = $user->transactions()
            ->whereIn('category_id', $categoryIds)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->join('financial_accounts', 'financial_accounts.id', '=', 'transactions.account_id')
            ->selectRaw('transactions.transaction_date as entry_date')
            ->selectRaw('financial_accounts.currency as currency')
            ->selectRaw('SUM(transactions.amount) as total')
            ->groupBy('transactions.transaction_date', 'financial_accounts.currency')
            ->orderBy('transactions.transaction_date')
            ->get();

        $daily = [];
        $dates = [];
        $totals = [];

        foreach ($rows as $row) {
            $date = CarbonImmutable::parse((string) $row->getAttribute('entry_date'))->format('Y-m-d');
            $currency = (string) $row->getAttribute('currency');
            $total = (string) $row->getAttribute('total');

            $daily[$date][$currency] = $total;
            $dates[$currency][$date] = $total;
            $totals[$currency] = isset($totals[$currency])
                ? bcadd($totals[$currency], $total, 4)
                : bcadd($total, '0', 4);
        }

        return [
            'daily' => $daily,
            'totals' => $totals,
            'dates' => $dates,
        ];
    }

    /**
     * @param  array<string, array<string, string>>  $daily
     * @return list<array{date: string, day: int, totals: array<string, string>}>
     */
    private function calendarGrid(CarbonImmutable $start, array $daily): array
    {
        $days = [];

        for ($day = 1; $day <= $start->daysInMonth; $day++) {
            $date = $start->format('Y-m-').str_pad((string) $day, 2, '0', STR_PAD_LEFT);
            $days[] = [
                'date' => $date,
                'day' => $day,
                'totals' => $daily[$date] ?? [],
            ];
        }

        return $days;
    }

    /**
     * @param array{
     *     daily: array<string, array<string, string>>,
     *     totals: array<string, string>,
     *     dates: array<string, array<string, string>>
     * } $data
     * @return array<string, array{
     *     total: string,
     *     avg: string,
     *     days: int,
     *     top: array{date: string, amount: string},
     *     low: array{date: string, amount: string}
     * }>
     */
    private function summaries(array $data): array
    {
        $summaries = [];

        foreach ($data['totals'] as $currency => $total) {
            $amounts = $data['dates'][$currency];
            $dayCount = count($amounts);

            if ($dayCount === 0) {
                continue;
            }

            $topDate = null;
            $top = null;
            $lowDate = null;
            $low = null;

            foreach ($amounts as $date => $amount) {
                if ($top === null || bccomp($amount, $top, 4) === 1) {
                    $top = $amount;
                    $topDate = $date;
                }
                if ($low === null || bccomp($amount, $low, 4) === -1) {
                    $low = $amount;
                    $lowDate = $date;
                }
            }

            $summaries[$currency] = [
                'total' => $total,
                'avg' => bcdiv($total, (string) $dayCount, 4),
                'days' => $dayCount,
                'top' => ['date' => (string) $topDate, 'amount' => (string) $top],
                'low' => ['date' => (string) $lowDate, 'amount' => (string) $low],
            ];
        }

        return $summaries;
    }

    /**
     * @param array{
     *     daily: array<string, array<string, string>>,
     *     totals: array<string, string>,
     *     dates: array<string, array<string, string>>
     * } $data
     */
    private function heatCurrency(array $data): ?string
    {
        $best = null;
        $bestAbs = null;

        foreach ($data['totals'] as $currency => $total) {
            $abs = bccomp($total, '0', 4) < 0 ? bcsub('0', $total, 4) : $total;

            if ($best === null || bccomp($abs, (string) $bestAbs, 4) === 1) {
                $best = $currency;
                $bestAbs = $abs;
            }
        }

        return $best;
    }

    /**
     * @return list<int>
     */
    private function categoryScopeIds(Category $category): array
    {
        $ids = [$category->id];
        $queue = $category->children()->pluck('id');

        while ($queue->isNotEmpty()) {
            $ids = array_merge($ids, $queue->all());
            $queue = Category::query()->whereIn('parent_id', $queue)->pluck('id');
        }

        return $ids;
    }
}
