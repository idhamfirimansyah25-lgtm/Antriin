<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use App\Models\Antrian;
use App\Observers\AntrianObserver;

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
        Antrian::observe(AntrianObserver::class);
    }
}
