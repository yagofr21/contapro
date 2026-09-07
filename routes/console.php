<?php

use App\Modules\Finance\Actions\GenerateScheduledTransactions;
use App\Modules\Finance\Actions\ProcessInstallments;
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

Artisan::command('recurring:generate', function (): void {
    $created = app(GenerateScheduledTransactions::class)->handle();
    $this->info("Recorrencias geradas: {$created}");
})->purpose('Materializa transacoes de recorrencias vencidas');

Artisan::command('installments:process', function (): void {
    $created = app(ProcessInstallments::class)->handle();
    $this->info("Parcelas processadas: {$created}");
})->purpose('Materializa transacoes de parcelas vencidas');

Schedule::command('market-data:sync')
    ->dailyAt('19:00')
    ->timezone('America/Sao_Paulo')
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('recurring:generate')
    ->dailyAt('02:30')
    ->timezone('America/Sao_Paulo')
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('installments:process')
    ->dailyAt('02:40')
    ->timezone('America/Sao_Paulo')
    ->onOneServer()
    ->withoutOverlapping();
