<?php

namespace App\Modules\Investment\Models;

use App\Models\User;
use Database\Factories\AssetPreferenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $asset_id
 * @property bool $auto_update
 * @property-read Asset $asset
 * @property-read User $user
 */
#[Fillable(['user_id', 'asset_id', 'auto_update'])]
#[UseFactory(AssetPreferenceFactory::class)]
class AssetPreference extends Model
{
    /** @use HasFactory<AssetPreferenceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'auto_update' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Asset, $this> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
