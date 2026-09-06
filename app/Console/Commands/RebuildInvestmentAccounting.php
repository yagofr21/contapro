<?php

namespace App\Console\Commands;

use App\Modules\Investment\Actions\RebuildPortfolioHolding;
use App\Modules\Investment\Models\AssetTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildInvestmentAccounting extends Command
{
    protected $signature = 'investments:rebuild-accounting';

    protected $description = 'Rebuild holdings and realized investment results from the operation ledger';

    public function handle(RebuildPortfolioHolding $rebuild): int
    {
        $pairs = AssetTransaction::query()
            ->select(['portfolio_id', 'asset_id'])
            ->distinct()
            ->orderBy('portfolio_id')
            ->orderBy('asset_id')
            ->get();

        foreach ($pairs as $pair) {
            DB::transaction(fn () => $rebuild->handle($pair->portfolio_id, $pair->asset_id));
        }

        $this->info(sprintf('%d posicoes recalculadas.', $pairs->count()));

        return self::SUCCESS;
    }
}
