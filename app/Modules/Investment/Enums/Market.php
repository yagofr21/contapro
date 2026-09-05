<?php

namespace App\Modules\Investment\Enums;

enum Market: string
{
    case B3 = 'B3';
    case Crypto = 'CRYPTO';
    case Nasdaq = 'NASDAQ';
    case Nyse = 'NYSE';
}
