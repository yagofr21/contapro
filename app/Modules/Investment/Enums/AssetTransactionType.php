<?php

namespace App\Modules\Investment\Enums;

enum AssetTransactionType: string
{
    case Buy = 'buy';
    case Dividend = 'dividend';
    case Interest = 'interest';
    case Sell = 'sell';
    case Split = 'split';
}
