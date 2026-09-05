<?php

namespace App\Modules\Investment\Actions;

use App\Modules\Investment\Models\AssetTransaction;
use Illuminate\Support\Facades\DB;

class DeleteAssetTransaction
{
    public function __construct(private RebuildPortfolioHolding $rebuild) {}

    public function handle(AssetTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            $portfolioId = $transaction->portfolio_id;
            $assetId = $transaction->asset_id;
            $transaction->delete();
            $this->rebuild->handle($portfolioId, $assetId);
        });
    }
}
