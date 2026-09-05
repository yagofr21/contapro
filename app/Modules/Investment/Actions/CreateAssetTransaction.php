<?php

namespace App\Modules\Investment\Actions;

use App\Modules\Investment\Models\AssetTransaction;
use App\Modules\Investment\Models\Portfolio;
use Illuminate\Support\Facades\DB;

class CreateAssetTransaction
{
    public function __construct(private RebuildPortfolioHolding $rebuild) {}

    /** @param array<string, mixed> $data */
    public function handle(Portfolio $portfolio, array $data): AssetTransaction
    {
        return DB::transaction(function () use ($portfolio, $data): AssetTransaction {
            $transaction = $portfolio->transactions()->create($data);
            $this->rebuild->handle($portfolio->id, (int) $transaction->asset_id);

            return $transaction;
        });
    }
}
