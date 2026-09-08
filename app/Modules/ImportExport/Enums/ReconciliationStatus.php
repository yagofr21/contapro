<?php

namespace App\Modules\ImportExport\Enums;

enum ReconciliationStatus: string
{
    case Previewed = 'previewed';
    case Reconciled = 'reconciled';
    case Divergent = 'divergent';
}
