<?php

namespace App\Providers;

use App\Services\Econt\EcontService;
use Illuminate\Support\ServiceProvider;
use App\Services\Econt\MockEcontService;

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
        //
    }
}
