<?php

namespace App\Modules\Finance\Enums;

enum CategoryType: string
{
    case Expense = 'expense';
    case Income = 'income';
}
