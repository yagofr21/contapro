<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReadinessController;
use App\Modules\Dashboard\Http\Controllers\DashboardController;
use App\Modules\Dashboard\Http\Controllers\ReportController;
use App\Modules\Finance\Http\Controllers\BudgetController;
use App\Modules\Finance\Http\Controllers\CategoryController;
use App\Modules\Finance\Http\Controllers\FinancialAccountController;
use App\Modules\Finance\Http\Controllers\TransactionController;
use App\Modules\ImportExport\Http\Controllers\TransactionCsvExportController;
use App\Modules\Investment\Http\Controllers\AssetController;
use App\Modules\Investment\Http\Controllers\AssetTransactionController;
use App\Modules\Investment\Http\Controllers\PortfolioController;
use App\Modules\MarketData\Http\Controllers\AssetQuoteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');
Route::get('/ready', ReadinessController::class)->name('ready');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/reports', ReportController::class)->name('reports.index');
    Route::get('/reports/transactions.csv', TransactionCsvExportController::class)
        ->name('reports.transactions.export');
    Route::resource('accounts', FinancialAccountController::class)->except('show');
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('budgets', BudgetController::class)->except('show');
    Route::resource('transactions', TransactionController::class)->except('show');
    Route::resource('portfolios', PortfolioController::class);
    Route::resource('assets', AssetController::class)->only(['index', 'create', 'store']);
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
