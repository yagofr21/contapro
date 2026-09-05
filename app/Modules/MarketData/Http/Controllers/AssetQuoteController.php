<?php

namespace App\Modules\MarketData\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Investment\Models\Asset;
use App\Modules\MarketData\Jobs\SyncQuote;
use Illuminate\Http\RedirectResponse;

class AssetQuoteController extends Controller
{
    public function __invoke(Asset $asset): RedirectResponse
    {
        $this->authorize('refresh', $asset);
        SyncQuote::dispatch($asset->id);

        return back()->with('success', 'Atualizacao da cotacao agendada.');
    }
}
