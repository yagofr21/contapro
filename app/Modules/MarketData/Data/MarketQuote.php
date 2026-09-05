<?php

namespace App\Modules\MarketData\Data;

final readonly class MarketQuote
{
    public function __construct(
        public string $priceDate,
        public ?string $open,
        public ?string $high,
        public ?string $low,
        public string $close,
        public ?string $adjustedClose,
        public ?int $volume,
    ) {}
}
