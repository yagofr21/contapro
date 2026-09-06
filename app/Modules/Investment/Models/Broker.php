<?php

namespace App\Modules\Investment\Models;

use App\Models\User;
use Database\Factories\BrokerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'name', 'is_active'])]
#[UseFactory(BrokerFactory::class)]
class Broker extends Model
{
    /** @use HasFactory<BrokerFactory> */
    use HasFactory, SoftDeletes;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<AssetTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(AssetTransaction::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
