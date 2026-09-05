<?php

namespace App\Modules\Investment\Policies;

use App\Models\User;
use App\Modules\Investment\Models\AssetTransaction;

class AssetTransactionPolicy
{
    public function update(User $user, AssetTransaction $transaction): bool
    {
        return $transaction->portfolio()->where('user_id', $user->id)->exists();
    }

    public function delete(User $user, AssetTransaction $transaction): bool
    {
        return $this->update($user, $transaction);
    }
}
