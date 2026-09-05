<?php

namespace App\Modules\MarketData\Actions;

use App\Modules\Investment\Models\Asset;
use App\Modules\MarketData\Data\MarketQuote;
use App\Modules\MarketData\Models\PriceHistory;

class PersistPriceHistory
{
    /** @param list<MarketQuote> $quotes */
    public function handle(Asset $asset, array $quotes): void
    {
        $now = now();
        $rows = array_map(fn (MarketQuote $quote): array => [
            'asset_id' => $asset->id,
            'price_date' => $quote->priceDate,
            'open' => $quote->open,
            'high' => $quote->high,
            'low' => $quote->low,
            'close' => $quote->close,
            'adjusted_close' => $quote->adjustedClose,
            'volume' => $quote->volume,
            'created_at' => $now,
            'updated_at' => $now,
        ], $quotes);

        if ($rows !== []) {
            PriceHistory::query()->upsert(
                $rows,
                ['asset_id', 'price_date'],
                ['open', 'high', 'low', 'close', 'adjusted_close', 'volume', 'updated_at'],
            );
        }
    }
}
