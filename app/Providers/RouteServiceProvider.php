<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        //
        $this->configureRateLimiting();
    }


    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(base_path('routes/api.php'));
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('create-dungeonroute', function (Request $request) {
            return Limit::perHour(20)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('create-tag', function (Request $request) {
            return Limit::perHour(60)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('create-team', function (Request $request) {
            return Limit::perHour(5)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('create-reports', function (Request $request) {
            return Limit::perHour(60)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('create-user', function (Request $request) {
            return Limit::perHour(5)->by($request->user()?->id ?: $request->ip());
        });

        // Heavy GET requests
        RateLimiter::for('search-dungeonroute', function (Request $request) {
            return Limit::perHour(300)->by($request->user()?->id ?: $request->ip());
        });

        // This consumes the same resources as creating a route - so we limit it
        RateLimiter::for('mdt-details', function (Request $request) {
            return Limit::perHour(60)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('mdt-export', function (Request $request) {
            return Limit::perHour(60)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('simulate', function (Request $request) {
            return Limit::perHour(120)->by($request->user()?->id ?: $request->ip());
        });
    }
}
