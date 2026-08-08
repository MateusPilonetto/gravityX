<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('auth', function (Request $request): array {
            $email = $request->input('email');
            $identity = is_string($email) ? strtolower(trim($email)) : 'unknown';

            return [
                Limit::perMinute(10)->by('auth-ip:'.$request->ip()),
                Limit::perMinute(5)->by('auth-identity:'.$identity),
            ];
        });

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by('api-user:'.($request->user()?->id ?? $request->ip())));
    }
}
