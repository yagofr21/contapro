<?php

namespace App\Modules\MarketData\Contracts;

use App\Modules\Investment\Enums\Market;
use App\Modules\MarketData\Data\MarketQuote;

interface MarketDataProvider
{
    public function quote(string $symbol): MarketQuote;

    /** @return list<MarketQuote> */
    public function quoteHistory(string $symbol, string $range = '1mo', string $interval = '1d'): array;

    /** @return list<Market> */
    public function supportedMarkets(): array;
}
