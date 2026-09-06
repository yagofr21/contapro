<?php

namespace App\Modules\Investment\Actions;

use App\Modules\Investment\Enums\AssetTransactionType;
use App\Modules\Investment\Models\AssetTransaction;
use App\Modules\Investment\Models\Portfolio;
use App\Modules\Investment\Models\PortfolioHolding;
use Illuminate\Validation\ValidationException;

class RebuildPortfolioHolding
{
    public function handle(int $portfolioId, int $assetId): ?PortfolioHolding
    {
        Portfolio::query()->whereKey($portfolioId)->lockForUpdate()->firstOrFail();

        $quantity = '0.00000000';
        $averageCost = '0.00000000';
        $transactions = AssetTransaction::query()
            ->where('portfolio_id', $portfolioId)
            ->where('asset_id', $assetId)
            ->orderBy('transaction_date')
            ->orderByRaw('CASE WHEN type = ? THEN 0 ELSE 1 END', [AssetTransactionType::Split->value])
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
                $averageCost = bcround(
                    bcdiv(bcadd($previousCost, $purchaseCost, 16), $quantity, 16),
                    8,
                );
                $this->clearRealizedResult($transaction);
            }

            if ($transaction->type === AssetTransactionType::Split) {
                if (bccomp($quantity, '0', 8) <= 0) {
                    throw ValidationException::withMessages([
                        'split_from' => 'O desdobramento exige uma posicao aberta nesta data.',
                    ]);
                }

                $positionCost = bcmul($quantity, $averageCost, 16);
                $quantity = bcround(bcdiv(
                    bcmul($quantity, $transaction->split_to ?? '0', 16),
                    $transaction->split_from ?? '0',
                    16,
                ), 8);

                if (bccomp($quantity, '0', 8) <= 0) {
                    throw ValidationException::withMessages([
                        'split_to' => 'A proporcao resulta em uma quantidade invalida.',
                    ]);
                }

                $averageCost = bcround(bcdiv($positionCost, $quantity, 16), 8);
                $this->clearRealizedResult($transaction);
            }

            if ($transaction->type === AssetTransactionType::Sell) {
                $remainingQuantity = bcsub($quantity, $transaction->quantity, 8);

                if (bccomp($remainingQuantity, '0', 8) < 0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'A venda excede a quantidade disponivel nesta data.',
                    ]);
                }

                $grossAmount = bcround(bcmul($transaction->quantity, $transaction->unit_price, 12), 4);
                $netAmount = bcsub($grossAmount, $transaction->fees, 4);

                if (bccomp($netAmount, '0', 4) < 0) {
                    throw ValidationException::withMessages([
                        'fees' => 'As taxas nao podem superar o valor bruto da venda.',
                    ]);
                }

                $realizedCostBasis = bcround(bcmul($transaction->quantity, $averageCost, 12), 4);
                $transaction->updateQuietly([
                    'gross_amount' => $grossAmount,
                    'net_amount' => $netAmount,
                    'realized_cost_basis' => $realizedCostBasis,
                    'realized_profit_loss' => bcsub($netAmount, $realizedCostBasis, 4),
                ]);
                $quantity = $remainingQuantity;
            }

            if (in_array($transaction->type, [
                AssetTransactionType::Dividend,
                AssetTransactionType::Interest,
            ], true)) {
                $this->clearRealizedResult($transaction);
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

    private function clearRealizedResult(AssetTransaction $transaction): void
    {
        if ($transaction->realized_cost_basis !== null || $transaction->realized_profit_loss !== null) {
            $transaction->updateQuietly([
                'realized_cost_basis' => null,
                'realized_profit_loss' => null,
            ]);
        }
    }
}
