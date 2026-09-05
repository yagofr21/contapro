<?php

namespace App\Modules\Finance\Enums;

enum BudgetPeriod: string
{
    case Custom = 'custom';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}
