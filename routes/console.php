<?php

use App\Modules\Investment\Enums\Market;
use App\Modules\Investment\Models\Asset;
use App\Modules\MarketData\Jobs\SyncQuote;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('market-data:sync', function (): void {
    Asset::query()
        ->where('is_active', true)
        ->whereIn('market', [Market::B3->value, Market::Crypto->value])
        ->select('id')
        ->chunkById(100, function ($assets): void {
            foreach ($assets as $asset) {
                SyncQuote::dispatch($asset->id);
            }
        });
})->purpose('Agenda a atualizacao de cotacoes dos ativos suportados');

Schedule::command('market-data:sync')
    ->dailyAt('19:00')
    ->timezone('America/Sao_Paulo')
    ->onOneServer()
    ->withoutOverlapping();
