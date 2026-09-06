<?php

namespace App\Modules\ImportExport\Enums;

enum ImportKind: string
{
    case Financial = 'financial';
    case Investment = 'investment';
}
