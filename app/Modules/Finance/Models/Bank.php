<?php

namespace App\Modules\Finance\Models;

use Database\Factories\BankFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $label
 * @property string $color
 * @property string $initials
 * @property bool $is_active
 */
#[Fillable(['code', 'label', 'color', 'initials', 'is_active'])]
#[UseFactory(BankFactory::class)]
class Bank extends Model
{
    /** @use HasFactory<BankFactory> */
    use HasFactory;

    /** @return HasMany<FinancialAccount, $this> */
    public function accounts(): HasMany
    {
        return $this->hasMany(FinancialAccount::class, 'bank', 'code');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
