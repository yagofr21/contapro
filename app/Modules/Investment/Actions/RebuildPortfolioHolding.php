<?php

namespace App\Modules\Investment\Actions;

use App\Modules\Investment\Enums\AssetTransactionType;
use App\Modules\Investment\Models\AssetTransaction;
use App\Modules\Investment\Models\PortfolioHolding;
use Illuminate\Validation\ValidationException;

class RebuildPortfolioHolding
{
    public function handle(int $portfolioId, int $assetId): ?PortfolioHolding
    {
        $quantity = '0.00000000';
        $averageCost = '0.00000000';

        $transactions = AssetTransaction::query()
            ->where('portfolio_id', $portfolioId)
            ->where('asset_id', $assetId)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        foreach ($transactions as $transaction) {
            if ($transaction->type === AssetTransactionType::Buy) {
                $previousCost = bcmul($quantity, $averageCost, 16);
                $purchaseCost = bcadd(
                    bcmul($transaction->quantity, $transaction->unit_price, 16),
                    $transaction->fees,
                    16,
                );
                $quantity = bcadd($quantity, $transaction->quantity, 8);
                $averageCost = bcdiv(bcadd($previousCost, $purchaseCost, 16), $quantity, 8);
            }

            if ($transaction->type === AssetTransactionType::Sell) {
                $quantity = bcsub($quantity, $transaction->quantity, 8);

                if (bccomp($quantity, '0', 8) < 0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'A venda excede a quantidade disponivel nesta data.',
                    ]);
                }
            }
        }

        if (bccomp($quantity, '0', 8) === 0) {
            PortfolioHolding::query()
                ->where('portfolio_id', $portfolioId)
                ->where('asset_id', $assetId)
                ->delete();

            return null;
        }

        return PortfolioHolding::query()->updateOrCreate(
            ['portfolio_id' => $portfolioId, 'asset_id' => $assetId],
            ['quantity' => $quantity, 'average_cost' => $averageCost],
        );
    }
}
