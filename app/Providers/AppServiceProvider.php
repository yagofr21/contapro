<?php

namespace App\Providers;

use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Installment;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Models\TransactionSchedule;
use App\Modules\Finance\Policies\BudgetPolicy;
use App\Modules\Finance\Policies\CategoryPolicy;
use App\Modules\Finance\Policies\FinancialAccountPolicy;
use App\Modules\Finance\Policies\InstallmentPolicy;
use App\Modules\Finance\Policies\TransactionPolicy;
use App\Modules\Finance\Policies\TransactionSchedulePolicy;
use App\Modules\ImportExport\Models\ImportBatch;
use App\Modules\ImportExport\Policies\ImportBatchPolicy;
use App\Modules\Investment\Models\Asset;
use App\Modules\Investment\Models\AssetTransaction;
use App\Modules\Investment\Models\Broker;
use App\Modules\Investment\Models\Portfolio;
use App\Modules\Investment\Policies\AssetPolicy;
use App\Modules\Investment\Policies\AssetTransactionPolicy;
use App\Modules\Investment\Policies\BrokerPolicy;
use App\Modules\Investment\Policies\PortfolioPolicy;
use App\Modules\MarketData\Contracts\MarketDataProvider;
use App\Modules\MarketData\Providers\BrapiProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MarketDataProvider::class, BrapiProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Gate::policy(FinancialAccount::class, FinancialAccountPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(Budget::class, BudgetPolicy::class);
        Gate::policy(TransactionSchedule::class, TransactionSchedulePolicy::class);
        Gate::policy(Installment::class, InstallmentPolicy::class);
        Gate::policy(Portfolio::class, PortfolioPolicy::class);
        Gate::policy(Asset::class, AssetPolicy::class);
        Gate::policy(AssetTransaction::class, AssetTransactionPolicy::class);
        Gate::policy(Broker::class, BrokerPolicy::class);
        Gate::policy(ImportBatch::class, ImportBatchPolicy::class);

        RateLimiter::for('brapi', fn (): Limit => Limit::perMinute(
            (int) config('services.brapi.requests_per_minute'),
        )->by('brapi'));

    }
}
