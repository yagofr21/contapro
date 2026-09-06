<?php

namespace App\Modules\ImportExport\Enums;

enum ImportStatus: string
{
    case Previewed = 'previewed';
    case Confirmed = 'confirmed';
    case Failed = 'failed';
}
