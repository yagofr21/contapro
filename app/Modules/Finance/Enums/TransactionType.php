<?php

namespace App\Modules\Finance\Enums;

enum TransactionType: string
{
    case Expense = 'expense';
    case Income = 'income';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
}
