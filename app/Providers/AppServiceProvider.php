<?php

namespace App\Providers;

use App\Exceptions\Handler;
use App\Models\Laratrust\Role;
use App\Models\User;
use App\Overrides\CustomRateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    private const ?int RATE_LIMIT_OVERRIDE_HTTP           = null;
    private const ?int RATE_LIMIT_OVERRIDE_PER_MINUTE_API = null;

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(!app()->isProduction());

        // Force HTTPS in production - these environments are running in AWS which terminates https at the load balancer
        // instead of at nginx, so the site will think it's serving http if it's not forced to https
        if (!$this->app->environment('local')) {
            URL::forceScheme('https');
        }

        Event::listen(SocialiteWasCalled::class, 'SocialiteProviders\\Battlenet\\BattlenetExtendSocialite@handle');
        Event::listen(SocialiteWasCalled::class, 'SocialiteProviders\\Discord\\DiscordExtendSocialite@handle');

        $this->app->bind(ExceptionHandler::class, Handler::class);

        $this->app->booted(function () {
//            /** @var User|null $user */
//            $user = Auth::user();
//
//            // https://docs.rollbar.com/docs/php-configuration-reference
//            Rollbar::init([
//                // I don't care about rollbar when developing locally
//                'enabled'       => !app()->isLocal(),
//                'access_token'  => config('keystoneguru.rollbar.server_access_token'),
//                'environment'   => config('app.env'),
//                'root'          => base_path(),
//                // @TODO I don't like this query here
//                'code_version'  => Release::latest()->first()->version,
//                'minimum_level' => Level::WARNING,
//                'person'        => [
//                    'id'       => optional($user)->id ?? 0,
//                    'username' => optional($user)->name,
//                ],
//                'custom'        => [
//                    'correlationId' => correlationId(),
//                ],
//            ]);
        });

        $this->configureRateLimiting();

        $this->configureApiRateLimiting();
    }

    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        // Bind our custom rate limiter
        $this->app->extend(RateLimiter::class, fn($command, $app) => new CustomRateLimiter($app->make('cache')->driver(
            $app['config']->get('cache.limiter'),
        )));
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('create-dungeonroute', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE_HTTP ?? 100)->by($this->userKey($request)));
        RateLimiter::for('create-tag', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE_HTTP ?? 60)->by($this->userKey($request)));
        RateLimiter::for('create-team', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE_HTTP ?? 5)->by($this->userKey($request)));
        RateLimiter::for('create-reports', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE_HTTP ?? 60)->by($this->userKey($request)));
        RateLimiter::for('create-user', function (Request $request) {
            // Bots somehow trigger /register?redirect=someurl a lot, so we have to catch it and not have them trigger the rate limiter
            // Besides, I only care about people creating new accounts, not "trying" to register
            if ($request->method() === 'GET') {
                return Limit::none();
            }

            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE_HTTP ?? 50)->by($this->userKey($request));
        });

        // Heavy GET requests
        RateLimiter::for('search-dungeonroute', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE_HTTP ?? 300)->by($this->userKey($request)));

        // This consumes the same resources as creating a route - so we limit it
        RateLimiter::for('mdt-details', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE_HTTP ?? 1200)->by($this->userKey($request)));
        RateLimiter::for('mdt-export', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE_HTTP ?? 1200)->by($this->userKey($request)));
        RateLimiter::for('simulate', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::RATE_LIMIT_OVERRIDE_HTTP ?? 120)->by($this->userKey($request)));
    }

    private function configureApiRateLimiting(): void
    {
        RateLimiter::for('api-general', fn(Request $request) => $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::RATE_LIMIT_OVERRIDE_PER_MINUTE_API ?? 900)->by($this->userKey($request)));
        RateLimiter::for('api-combatlog-create-dungeonroute', fn(Request $request) => $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::RATE_LIMIT_OVERRIDE_PER_MINUTE_API ?? 120)->by($this->userKey($request)));
        RateLimiter::for('api-combatlog-correct-event', fn(Request $request) => $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::RATE_LIMIT_OVERRIDE_PER_MINUTE_API ?? 900)->by($this->userKey($request)));
        RateLimiter::for('api-create-dungeonroute-thumbnail', fn(Request $request) => $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::RATE_LIMIT_OVERRIDE_PER_MINUTE_API ?? 30)->by($this->userKey($request)));
    }

    private function noLimitForExemptions(Request $request): ?Limit
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user?->hasRole(Role::roles([
            Role::ROLE_ADMIN,
            Role::ROLE_INTERNAL_TEAM,
        ]))) {
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
