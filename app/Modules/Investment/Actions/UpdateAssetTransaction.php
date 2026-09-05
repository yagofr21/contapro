<?php

namespace App\Modules\Investment\Actions;

use App\Modules\Investment\Models\AssetTransaction;
use Illuminate\Support\Facades\DB;

class UpdateAssetTransaction
{
    public function __construct(private RebuildPortfolioHolding $rebuild) {}

    /** @param array<string, mixed> $data */
    public function handle(AssetTransaction $transaction, array $data): AssetTransaction
    {
        return DB::transaction(function () use ($transaction, $data): AssetTransaction {
            $previousAssetId = $transaction->asset_id;
            $transaction->update($data);
            $this->rebuild->handle($transaction->portfolio_id, (int) $previousAssetId);
            $this->rebuild->handle($transaction->portfolio_id, (int) $transaction->asset_id);

            return $transaction->refresh();
        });
    }
}
