<?php

namespace App\Modules\Investment\Policies;

use App\Models\User;
use App\Modules\Investment\Enums\Market;
use App\Modules\Investment\Models\Asset;

class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function refresh(User $user, Asset $asset): bool
    {
        return $asset->is_active
            && in_array($asset->market, [Market::B3, Market::Crypto], true)
            && $asset->holdings()
                ->whereHas('portfolio', fn ($query) => $query->where('user_id', $user->id))
                ->exists();
    }
}
