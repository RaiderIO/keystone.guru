<?php

namespace App\Providers;

use App\Http\Middleware\Api\ApiMetrics;
use App\Models\Laratrust\Role;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    private const RATE_LIMIT_OVERRIDE_HTTP           = null;
    private const RATE_LIMIT_OVERRIDE_PER_MINUTE_API = null;

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        parent::boot();

        $this->configureRateLimiting();

        $this->configureApiRateLimiting();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();
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
            ->middleware(['api', 'throttle:api-general', ApiMetrics::class])
            ->group(base_path('routes/api.php'));
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('create-dungeonroute', function (Request $request) {
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE_HTTP ?? 20)->by($this->userKey($request));
        });
        RateLimiter::for('create-tag', function (Request $request) {
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE_HTTP ?? 60)->by($this->userKey($request));
        });
        RateLimiter::for('create-team', function (Request $request) {
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE_HTTP ?? 5)->by($this->userKey($request));
        });
        RateLimiter::for('create-reports', function (Request $request) {
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE_HTTP ?? 60)->by($this->userKey($request));
        });
        RateLimiter::for('create-user', function (Request $request) {
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE_HTTP ?? 50)->by($this->userKey($request));
        });

        // Heavy GET requests
        RateLimiter::for('search-dungeonroute', function (Request $request) {
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE_HTTP ?? 300)->by($this->userKey($request));
        });

        // This consumes the same resources as creating a route - so we limit it
        RateLimiter::for('mdt-details', function (Request $request) {
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE_HTTP ?? 60)->by($this->userKey($request));
        });
        RateLimiter::for('mdt-export', function (Request $request) {
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE_HTTP ?? 60)->by($this->userKey($request));
        });
        RateLimiter::for('simulate', function (Request $request) {
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE_HTTP ?? 120)->by($this->userKey($request));
        });
    }

    private function configureApiRateLimiting(): void
    {
        RateLimiter::for('api-general', function (Request $request) {
            return $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::RATE_LIMIT_OVERRIDE_PER_MINUTE_API ?? 600)->by($this->userKey($request));
        });
        RateLimiter::for('api-combatlog-create-dungeonroute', function (Request $request) {
            return $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::RATE_LIMIT_OVERRIDE_PER_MINUTE_API ?? 120)->by($this->userKey($request));
        });
        RateLimiter::for('api-combatlog-correct-event', function (Request $request) {
            return $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::RATE_LIMIT_OVERRIDE_PER_MINUTE_API ?? 900)->by($this->userKey($request));
        });
        RateLimiter::for('api-create-dungeonroute-thumbnail', function (Request $request) {
            return $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::RATE_LIMIT_OVERRIDE_PER_MINUTE_API ?? 30)->by($this->userKey($request));
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

    private function noLimitForExemptionsApi(Request $request): ?Limit
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user?->hasRole(Role::ROLE_ADMIN)) {
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
