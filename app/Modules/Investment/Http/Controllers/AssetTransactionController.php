<?php

namespace App\Modules\Investment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Investment\Actions\CreateAssetTransaction;
use App\Modules\Investment\Actions\DeleteAssetTransaction;
use App\Modules\Investment\Actions\UpdateAssetTransaction;
use App\Modules\Investment\Http\Requests\AssetTransactionRequest;
use App\Modules\Investment\Models\Asset;
use App\Modules\Investment\Models\AssetTransaction;
use App\Modules\Investment\Models\Portfolio;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AssetTransactionController extends Controller
{
    public function create(Portfolio $portfolio): Response
    {
        $this->authorize('view', $portfolio);

        return Inertia::render('InvestmentTransactions/Create', [
            'portfolio' => ['id' => $portfolio->id, 'name' => $portfolio->name],
            'assets' => $this->assets($portfolio->currency->value),
        ]);
    }

    public function store(
        AssetTransactionRequest $request,
        Portfolio $portfolio,
        CreateAssetTransaction $action,
    ): RedirectResponse {
        $action->handle($portfolio, $request->validated());

        return to_route('portfolios.show', $portfolio)->with('success', 'Operacao registrada com sucesso.');
    }

    public function edit(AssetTransaction $investmentTransaction): Response
    {
        $this->authorize('update', $investmentTransaction);

        return Inertia::render('InvestmentTransactions/Edit', [
            'portfolio' => [
                'id' => $investmentTransaction->portfolio_id,
                'name' => $investmentTransaction->portfolio->name,
            ],
            'assets' => $this->assets($investmentTransaction->portfolio->currency->value),
            'transaction' => [
                'id' => $investmentTransaction->id,
                'asset_id' => $investmentTransaction->asset_id,
                'type' => $investmentTransaction->type->value,
                'quantity' => $investmentTransaction->quantity,
                'unit_price' => $investmentTransaction->unit_price,
                'fees' => $investmentTransaction->fees,
                'gross_amount' => $investmentTransaction->gross_amount,
                'net_amount' => $investmentTransaction->net_amount,
                'transaction_date' => $investmentTransaction->transaction_date->format('Y-m-d'),
                'note' => $investmentTransaction->note,
            ],
        ]);
    }

    public function update(
        AssetTransactionRequest $request,
        AssetTransaction $investmentTransaction,
        UpdateAssetTransaction $action,
    ): RedirectResponse {
        $action->handle($investmentTransaction, $request->validated());

        return to_route('portfolios.show', $investmentTransaction->portfolio_id)
            ->with('success', 'Operacao atualizada com sucesso.');
    }

    public function destroy(
        AssetTransaction $investmentTransaction,
        DeleteAssetTransaction $action,
    ): RedirectResponse {
        $this->authorize('delete', $investmentTransaction);
        $portfolioId = $investmentTransaction->portfolio_id;
        $action->handle($investmentTransaction);

        return to_route('portfolios.show', $portfolioId)->with('success', 'Operacao removida com sucesso.');
    }

    /** @return array<int, array{id: int, symbol: string, name: string, currency: string, market: string}> */
    private function assets(string $currency): array
    {
        return Asset::query()
            ->where('is_active', true)
            ->where('currency', $currency)
            ->orderBy('symbol')
            ->get(['id', 'symbol', 'name', 'currency', 'market'])
            ->map(fn (Asset $asset) => [
                'id' => $asset->id,
                'symbol' => $asset->symbol,
                'name' => $asset->name,
                'currency' => $asset->currency->value,
                'market' => $asset->market->value,
            ])
            ->all();
    }
}
