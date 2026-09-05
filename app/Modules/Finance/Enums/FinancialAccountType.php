<?php

namespace App\Modules\Finance\Enums;

enum FinancialAccountType: string
{
    case Cash = 'cash';
    case Checking = 'checking';
    case CreditCard = 'credit_card';
    case Investment = 'investment';
    case Savings = 'savings';
}
