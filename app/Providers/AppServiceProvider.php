<?php

namespace App\Providers;

use App\Models\Sales;
use App\Models\Lifting;
use App\Observers\SalesObserver;
use App\Observers\LiftingObserver;
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
        Lifting::observe(LiftingObserver::class);
        Sales::observe(SalesObserver::class);
    }
}
