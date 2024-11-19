<?php

namespace App\Providers;

use App\Models\Laratrust\Role;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    private const RATE_LIMIT_OVERRIDE = 999999;

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
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE ?? 20)->by($this->userKey($request));
        });
        RateLimiter::for('create-tag', function (Request $request) {
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE ?? 60)->by($this->userKey($request));
        });
        RateLimiter::for('create-team', function (Request $request) {
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE ?? 5)->by($this->userKey($request));
        });
        RateLimiter::for('create-reports', function (Request $request) {
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE ?? 60)->by($this->userKey($request));
        });
        RateLimiter::for('create-user', function (Request $request) {
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE ?? 5)->by($this->userKey($request));
        });

        // Heavy GET requests
        RateLimiter::for('search-dungeonroute', function (Request $request) {
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE ?? 300)->by($this->userKey($request));
        });

        // This consumes the same resources as creating a route - so we limit it
        RateLimiter::for('mdt-details', function (Request $request) {
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE ?? 60)->by($this->userKey($request));
        });
        RateLimiter::for('mdt-export', function (Request $request) {
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE ?? 60)->by($this->userKey($request));
        });
        RateLimiter::for('simulate', function (Request $request) {
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE ?? 120)->by($this->userKey($request));
        });
    }

    private function noLimitForExemptions(Request $request): ?Limit
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user?->hasRole(Role::ROLE_ADMIN) || $user?->hasRole(Role::ROLE_INTERNAL_TEAM)) {
            return Limit::none();
        }

        return null;
    }

    private function userKey(Request $request): string
    {
        /** @var User|null $user */
        $user = $request->user();
        return $user?->id ?: $request->ip();
    }
}
