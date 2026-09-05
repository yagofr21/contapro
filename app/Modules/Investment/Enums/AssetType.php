<?php

namespace App\Modules\Investment\Enums;

enum AssetType: string
{
    case Bond = 'bond';
    case Crypto = 'crypto';
    case Etf = 'etf';
    case Fii = 'fii';
    case Reit = 'reit';
    case Stock = 'stock';
}
