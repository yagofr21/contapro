<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReadinessController;
use App\Modules\Dashboard\Http\Controllers\DashboardController;
use App\Modules\Dashboard\Http\Controllers\ReportController;
use App\Modules\Finance\Http\Controllers\BudgetController;
use App\Modules\Finance\Http\Controllers\CategoryController;
use App\Modules\Finance\Http\Controllers\FinancialAccountController;
use App\Modules\Finance\Http\Controllers\TransactionController;
use App\Modules\ImportExport\Http\Controllers\CsvImportController;
use App\Modules\ImportExport\Http\Controllers\ImportTemplateController;
use App\Modules\ImportExport\Http\Controllers\InvestmentCsvExportController;
use App\Modules\ImportExport\Http\Controllers\TransactionCsvExportController;
use App\Modules\Investment\Http\Controllers\AssetController;
use App\Modules\Investment\Http\Controllers\AssetTransactionController;
use App\Modules\Investment\Http\Controllers\BrokerController;
use App\Modules\Investment\Http\Controllers\PortfolioController;
use App\Modules\MarketData\Http\Controllers\AssetQuoteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');
Route::get('/ready', ReadinessController::class)->name('ready');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/reports', ReportController::class)->name('reports.index');
    Route::get('/reports/transactions.csv', TransactionCsvExportController::class)
        ->name('reports.transactions.export');
    Route::get('/imports', [CsvImportController::class, 'index'])->name('imports.index');
    Route::get('/imports/template/{kind}', ImportTemplateController::class)->name('imports.template');
    Route::post('/imports', [CsvImportController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('imports.store');
    Route::get('/imports/{importBatch}', [CsvImportController::class, 'show'])->name('imports.show');
    Route::post('/imports/{importBatch}/confirm', [CsvImportController::class, 'confirm'])
        ->middleware('throttle:10,1')
        ->name('imports.confirm');
    Route::delete('/imports/{importBatch}', [CsvImportController::class, 'destroy'])->name('imports.destroy');
    Route::resource('accounts', FinancialAccountController::class)->except('show');
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('budgets', BudgetController::class)->except('show');
    Route::resource('transactions', TransactionController::class)->except('show');
    Route::get('portfolios/{portfolio}/operations.csv', InvestmentCsvExportController::class)
        ->name('portfolios.operations.export');
    Route::resource('portfolios', PortfolioController::class);
    Route::resource('assets', AssetController::class)->only(['index', 'create', 'store']);
    Route::resource('brokers', BrokerController::class)->except(['show', 'destroy']);
    Route::post('assets/{asset}/refresh', AssetQuoteController::class)
        ->middleware('throttle:5,1')
        ->name('assets.refresh');
    Route::get('portfolios/{portfolio}/operations/create', [AssetTransactionController::class, 'create'])
        ->name('investment-transactions.create');
    Route::post('portfolios/{portfolio}/operations', [AssetTransactionController::class, 'store'])
        ->name('investment-transactions.store');
    Route::resource('investment-transactions', AssetTransactionController::class)
        ->only(['edit', 'update', 'destroy']);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
