<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('auth-api', function (Request $request) {
            $email = mb_strtolower((string) $request->input('email', ''));

            return [
                Limit::perMinute(10)->by($request->ip().'|'.$email),
            ];
        });

        RateLimiter::for('contact-form', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
            ];
        });

        RateLimiter::for('checkout-api', function (Request $request) {
            return [
                Limit::perMinute(20)->by($request->ip()),
            ];
        });

        RateLimiter::for('econt-lookup', function (Request $request) {
            return [
                Limit::perMinute(30)->by($request->ip()),
            ];
        });
    }
}
