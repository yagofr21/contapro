<?php

namespace App\Modules\Investment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Investment\Actions\CreateAssetTransaction;
use App\Modules\Investment\Actions\DeleteAssetTransaction;
use App\Modules\Investment\Actions\UpdateAssetTransaction;
use App\Modules\Investment\Enums\AssetTransactionType;
use App\Modules\Investment\Http\Requests\AssetTransactionRequest;
use App\Modules\Investment\Models\Asset;
use App\Modules\Investment\Models\AssetTransaction;
use App\Modules\Investment\Models\Broker;
use App\Modules\Investment\Models\Portfolio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssetTransactionController extends Controller
{
    public function create(Request $request, Portfolio $portfolio): Response
    {
        $this->authorize('view', $portfolio);
        $assets = $this->assets($portfolio);
        $requestedAssetId = $request->integer('asset');

        return Inertia::render('InvestmentTransactions/Create', [
            'portfolio' => [
                'id' => $portfolio->id,
                'name' => $portfolio->name,
                'currency' => $portfolio->currency->value,
            ],
            'assets' => $assets,
            'brokers' => $this->brokers((int) $request->user()->id),
            'selectedAssetId' => in_array($requestedAssetId, array_column($assets, 'id'), true)
                ? $requestedAssetId
                : null,
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
                'currency' => $investmentTransaction->portfolio->currency->value,
            ],
            'assets' => $this->assets($investmentTransaction->portfolio, $investmentTransaction),
            'brokers' => $this->brokers(
                $investmentTransaction->portfolio->user_id,
                $investmentTransaction->broker_id,
            ),
            'transaction' => [
                'id' => $investmentTransaction->id,
                'asset_id' => $investmentTransaction->asset_id,
                'broker_id' => $investmentTransaction->broker_id,
                'type' => $investmentTransaction->type->value,
                'quantity' => $investmentTransaction->quantity,
                'unit_price' => $investmentTransaction->unit_price,
                'fees' => $investmentTransaction->fees,
                'split_from' => $investmentTransaction->split_from,
                'split_to' => $investmentTransaction->split_to,
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

    /** @return array<int, array{id: int, symbol: string, name: string, currency: string, market: string, available_quantity: string}> */
    private function assets(Portfolio $portfolio, ?AssetTransaction $transaction = null): array
    {
        $availableQuantities = $portfolio->holdings()->pluck('quantity', 'asset_id');

        return Asset::query()
            ->where('is_active', true)
            ->where('currency', $portfolio->currency->value)
            ->orderBy('symbol')
            ->get(['id', 'symbol', 'name', 'currency', 'market'])
            ->map(function (Asset $asset) use ($availableQuantities, $transaction): array {
                $availableQuantity = (string) ($availableQuantities->get($asset->id) ?? '0');

                if ($transaction?->asset_id === $asset->id
                    && $transaction->type === AssetTransactionType::Sell) {
                    $availableQuantity = bcadd($availableQuantity, $transaction->quantity, 8);
                }

                return [
                    'id' => $asset->id,
                    'symbol' => $asset->symbol,
                    'name' => $asset->name,
                    'currency' => $asset->currency->value,
                    'market' => $asset->market->value,
                    'available_quantity' => $availableQuantity,
                ];
            })
            ->all();
    }

    /** @return array<int, array{id: int, name: string, is_active: bool}> */
    private function brokers(int $userId, ?int $currentBrokerId = null): array
    {
        return Broker::query()
            ->where('user_id', $userId)
            ->where(fn ($query) => $query
                ->where('is_active', true)
                ->when($currentBrokerId !== null, fn ($query) => $query->orWhereKey($currentBrokerId)))
            ->orderBy('name')
            ->get(['id', 'name', 'is_active'])
            ->map(fn (Broker $broker) => [
                'id' => $broker->id,
                'name' => $broker->name,
                'is_active' => $broker->is_active,
            ])
            ->all();
    }
}
