<?php

namespace App\Modules\Investment\Http\Controllers;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Modules\Investment\Actions\CreatePortfolio;
use App\Modules\Investment\Actions\UpdatePortfolio;
use App\Modules\Investment\Enums\AssetTransactionType;
use App\Modules\Investment\Http\Requests\PortfolioRequest;
use App\Modules\Investment\Models\Portfolio;
use App\Modules\Investment\Models\PortfolioHolding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Portfolio::class);

        $portfolios = $request->user()->portfolios()
            ->with('holdings.asset.latestPrice')
            ->orderBy('name')
            ->get()
            ->map(function (Portfolio $portfolio): array {
                $cost = '0.0000';
                $currentValue = '0.0000';

                foreach ($portfolio->holdings as $holding) {
                    $cost = bcadd($cost, bcmul($holding->quantity, $holding->average_cost, 4), 4);
                    $latestPrice = $holding->asset->latestPrice;
                    $price = $latestPrice !== null
                        ? ($latestPrice->adjusted_close ?? $latestPrice->close)
                        : $holding->average_cost;
                    $currentValue = bcadd($currentValue, bcmul($holding->quantity, $price, 4), 4);
                }

                return [
                    'id' => $portfolio->id,
                    'name' => $portfolio->name,
                    'currency' => $portfolio->currency->value,
                    'holdings_count' => $portfolio->holdings->count(),
                    'cost' => $cost,
                    'current_value' => $currentValue,
                    'return' => bcsub($currentValue, $cost, 4),
                ];
            });

        return Inertia::render('Portfolios/Index', ['portfolios' => $portfolios]);
    }

    public function create(): Response
    {
        $this->authorize('create', Portfolio::class);

        return Inertia::render('Portfolios/Create', $this->formOptions());
    }

    public function store(PortfolioRequest $request, CreatePortfolio $action): RedirectResponse
    {
        $portfolio = $action->handle($request->user(), $request->validated());

        return to_route('portfolios.show', $portfolio)->with('success', 'Carteira criada com sucesso.');
    }

    public function show(Portfolio $portfolio): Response
    {
        $this->authorize('view', $portfolio);
        $portfolio->load([
            'holdings.asset.latestPrice',
            'transactions' => fn ($query) => $query->with('asset:id,symbol,name,currency')->latest('transaction_date')->latest('id'),
        ]);
        $holdings = $portfolio->holdings->map(fn (PortfolioHolding $holding) => $this->serializeHolding($holding));
        $summary = $holdings->reduce(fn (array $total, array $holding): array => [
            'cost' => bcadd($total['cost'], $holding['cost'], 4),
            'current_value' => bcadd($total['current_value'], $holding['current_value'], 4),
            'return' => bcadd($total['return'], $holding['return'], 4),
        ], ['cost' => '0.0000', 'current_value' => '0.0000', 'return' => '0.0000']);
        $summary['net_income'] = $portfolio->transactions
            ->whereIn('type', [AssetTransactionType::Dividend, AssetTransactionType::Interest])
            ->reduce(
                fn (string $total, $transaction): string => bcadd($total, $transaction->net_amount ?? '0', 4),
                '0.0000',
            );

        return Inertia::render('Portfolios/Show', [
            'portfolio' => [
                'id' => $portfolio->id,
                'name' => $portfolio->name,
                'currency' => $portfolio->currency->value,
            ],
            'summary' => $summary,
            'holdings' => $holdings,
            'transactions' => $portfolio->transactions->map(fn ($transaction) => [
                'id' => $transaction->id,
                'asset_symbol' => $transaction->asset->symbol,
                'asset_name' => $transaction->asset->name,
                'type' => $transaction->type->value,
                'quantity' => $transaction->quantity,
                'unit_price' => $transaction->unit_price,
                'fees' => $transaction->fees,
                'gross_amount' => $transaction->gross_amount,
                'net_amount' => $transaction->net_amount,
                'date' => $transaction->transaction_date->format('Y-m-d'),
                'note' => $transaction->note,
            ]),
        ]);
    }

    public function edit(Portfolio $portfolio): Response
    {
        $this->authorize('update', $portfolio);

        return Inertia::render('Portfolios/Edit', [
            ...$this->formOptions(),
            'portfolio' => [
                'id' => $portfolio->id,
                'name' => $portfolio->name,
                'currency' => $portfolio->currency->value,
            ],
        ]);
    }

    public function update(PortfolioRequest $request, Portfolio $portfolio, UpdatePortfolio $action): RedirectResponse
    {
        $action->handle($portfolio, $request->validated());

        return to_route('portfolios.show', $portfolio)->with('success', 'Carteira atualizada com sucesso.');
    }

    public function destroy(Portfolio $portfolio): RedirectResponse
    {
        $this->authorize('delete', $portfolio);
        $portfolio->delete();

        return to_route('portfolios.index')->with('success', 'Carteira removida com sucesso.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return ['currencies' => collect(Currency::cases())->map(fn ($currency) => [
            'value' => $currency->value,
            'label' => $currency->value,
        ])];
    }

    /** @return array<string, mixed> */
    private function serializeHolding(PortfolioHolding $holding): array
    {
        $latestPrice = $holding->asset->latestPrice;
        $price = $latestPrice !== null
            ? ($latestPrice->adjusted_close ?? $latestPrice->close)
            : $holding->average_cost;
        $cost = bcmul($holding->quantity, $holding->average_cost, 4);
        $currentValue = bcmul($holding->quantity, $price, 4);

        return [
            'id' => $holding->id,
            'asset_id' => $holding->asset_id,
            'symbol' => $holding->asset->symbol,
            'name' => $holding->asset->name,
            'type' => $holding->asset->type->value,
            'currency' => $holding->asset->currency->value,
            'quantity' => $holding->quantity,
            'average_cost' => $holding->average_cost,
            'current_price' => $price,
            'cost' => $cost,
            'current_value' => $currentValue,
            'return' => bcsub($currentValue, $cost, 4),
        ];
    }
}
