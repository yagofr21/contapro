<?php

namespace App\Modules\Investment\Queries;

use App\Models\User;
use App\Modules\Investment\Enums\AssetTransactionType;

class PortfolioValuationQuery
{
    /**
     * @return array{
     *     summaries: list<array{currency: string, cost: string, current_value: string, market_return: string, market_return_percentage: string, net_income: string, unpriced_holdings: int, price_date: string|null}>,
     *     allocation: list<array{currency: string, type: string, value: string}>
     * }
     */
    public function forUser(User $user): array
    {
        $portfolios = $user->portfolios()
            ->with([
                'holdings.asset.latestPrice',
                'transactions' => fn ($query) => $query->whereIn('type', [
                    AssetTransactionType::Dividend->value,
                    AssetTransactionType::Interest->value,
                ]),
            ])
            ->get();
        $summaries = [];
        $allocation = [];

        foreach ($portfolios as $portfolio) {
            $currency = $portfolio->currency->value;
            $summaries[$currency] ??= [
                'currency' => $currency,
                'cost' => '0.0000',
                'current_value' => '0.0000',
                'market_return' => '0.0000',
                'market_return_percentage' => '0.0000',
                'net_income' => '0.0000',
                'unpriced_holdings' => 0,
                'price_date' => null,
            ];

            foreach ($portfolio->holdings as $holding) {
                $latestPrice = $holding->asset->latestPrice;
                $price = $latestPrice !== null
                    ? ($latestPrice->adjusted_close ?? $latestPrice->close)
                    : $holding->average_cost;
                $cost = bcround(bcmul($holding->quantity, $holding->average_cost, 12), 4);
                $value = bcround(bcmul($holding->quantity, $price, 12), 4);
                $summaries[$currency]['cost'] = bcadd($summaries[$currency]['cost'], $cost, 4);
                $summaries[$currency]['current_value'] = bcadd($summaries[$currency]['current_value'], $value, 4);

                if ($latestPrice === null) {
                    $summaries[$currency]['unpriced_holdings']++;
                } else {
                    $date = $latestPrice->price_date->format('Y-m-d');
                    $currentDate = $summaries[$currency]['price_date'];
                    $summaries[$currency]['price_date'] = $currentDate === null || $date < $currentDate ? $date : $currentDate;
                }

                $key = $currency.':'.$holding->asset->type->value;
                $allocation[$key] ??= ['currency' => $currency, 'type' => $holding->asset->type->value, 'value' => '0.0000'];
                $allocation[$key]['value'] = bcadd($allocation[$key]['value'], $value, 4);
            }

            foreach ($portfolio->transactions as $transaction) {
                $summaries[$currency]['net_income'] = bcadd(
                    $summaries[$currency]['net_income'],
                    $transaction->net_amount ?? '0',
                    4,
                );
            }
        }

        foreach ($summaries as &$summary) {
            $summary['market_return'] = bcsub($summary['current_value'], $summary['cost'], 4);
            $summary['market_return_percentage'] = bccomp($summary['cost'], '0', 4) === 0
                ? '0.0000'
                : bcround(bcmul(bcdiv($summary['market_return'], $summary['cost'], 12), '100', 12), 4);
        }
        unset($summary);

        return ['summaries' => array_values($summaries), 'allocation' => array_values($allocation)];
    }
}
