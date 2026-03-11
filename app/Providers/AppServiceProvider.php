<?php

namespace App\Providers;

use App\Models\DailyJournal;
use App\Models\Trade;
use App\Models\TradingAccount;
use App\Policies\DailyJournalPolicy;
use App\Policies\TradePolicy;
use App\Policies\TradingAccountPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Trade::class, TradePolicy::class);
        Gate::policy(DailyJournal::class, DailyJournalPolicy::class);
        Gate::policy(TradingAccount::class, TradingAccountPolicy::class);

        Vite::prefetch(concurrency: 3);
    }
}
