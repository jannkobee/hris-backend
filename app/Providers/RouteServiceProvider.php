<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/backend';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(500)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(implode('|', [
                $request->getHost(),
                $request->ip(),
                strtolower((string) $request->input('email')),
            ]));
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)->by($request->getHost().'|'.$request->ip());
        });

        RateLimiter::for('mfa-challenge', function (Request $request) {
            return Limit::perMinute(5)->by($request->getHost().'|'.$request->ip());
        });

        RateLimiter::for('platform-provisioning', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('trial-signup', function (Request $request) {
            return Limit::perHour(3)->by($request->ip().'|'.strtolower((string) $request->input('email')));
        });

        $this->routes(function () {
            Route::prefix(self::HOME)
                ->group(function () {
                    Route::middleware('api')
                        ->prefix('api/v1')
                        ->group(base_path('routes/api.php'));

                    Route::middleware('web')
                        ->group(base_path('routes/web.php'));
                });
        });
    }
}
