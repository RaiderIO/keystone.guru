<?php

namespace App\Providers;

use App\Exceptions\Handler;
use App\Models\Feature\Feature;
use App\Models\Laratrust\Role;
use App\Models\User;
use App\Overrides\AiLocaleMessageSelector;
use App\Overrides\CustomRateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Translation\Translator;
use Override;
use Rollbar\Payload\Level;
use Rollbar\Rollbar;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    private static ?int $rateLimitOverrideHttp         = null; // @phpstan-ignore property.unusedType
    private static ?int $rateLimitOverridePerMinuteApi = null; // @phpstan-ignore property.unusedType

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Lazy loading is never allowed silently: in dev/test a missing eager-load throws so it is caught
        // immediately, while in production it is logged (and the relation still lazy-loads) so users are
        // never served an error but we get told exactly which relation to eager-load.
        Model::preventLazyLoading();
        Model::handleLazyLoadingViolationUsing(static function (Model $model, string $relation): void {
            if (!app()->isProduction()) {
                throw new LazyLoadingViolationException($model, $relation);
            }

            Log::warning(sprintf('Lazy loading violation: %s lazy-loaded relation [%s]', $model::class, $relation));
        });

        // Force HTTPS in production - these environments are running in AWS which terminates https at the load balancer
        // instead of at nginx, so the site will think it's serving http if it's not forced to https
        if (!$this->app->environment('local')) {
            URL::forceScheme('https');
        }

        Event::listen(SocialiteWasCalled::class, 'SocialiteProviders\\Battlenet\\BattlenetExtendSocialite@handle');
        Event::listen(SocialiteWasCalled::class, 'SocialiteProviders\\Discord\\DiscordExtendSocialite@handle');

        // Requests get a trace_id from the AddsTraceIdToContext middleware - console commands get theirs here, so
        // long-running commands (and the jobs they dispatch, Context is dehydrated into the job payload) are
        // traceable the same way
        Event::listen(CommandStarting::class, static function (): void {
            Context::addIf('trace_id', (string)Str::uuid());
        });

        // Every feature in App\Features resolves off the user's roles, but Pennant stores the value it resolved
        // once and keeps serving that forever after - so changing a user's roles has to drop their stored values.
        // Laratrust passes the changed user as the first argument; the arguments following it differ per event
        // (added/removed pass the role, synced passes the sync changes) so they're deliberately not accepted here.
        $forgetFeatures = static function (User $user): void {
            Feature::forgetAllForUser($user);
        };

        // removeRoles() loops removeRole() - unless it's handed an empty array, in which case it syncs instead
        User::roleAdded($forgetFeatures);
        User::roleRemoved($forgetFeatures);
        User::roleSynced($forgetFeatures);

        $this->app->bind(ExceptionHandler::class, Handler::class);

        $this->app->booted(function () {
            /** @var User|null $user */
            $user = Auth::user();

            // https://docs.rollbar.com/docs/php-configuration-reference
            Rollbar::init([
                // I don't care about rollbar when developing locally, and CI/PHPUnit runs shouldn't send noise either
                'enabled' => !app()->isLocal() && !app()->runningUnitTests(),
                // The access token is blank (not unset) in every non-production .env.*.example, and Rollbar's own
                // config validation rejects an empty string even when 'enabled' is false above
                'access_token' => config('keystoneguru.rollbar.server_access_token') ?: null,
                'environment'  => config('app.env'),
                'root'         => base_path(),
                // Read the version file directly rather than through ViewServiceInterface - this callback runs on
                // every console command boot (not just HTTP requests), and ViewService's cache-backed lookup
                // requires Redis, which isn't available to every console context (e.g. the CI phpstan job)
                'code_version'  => trim((string)file_get_contents(base_path('version'))),
                'minimum_level' => Level::WARNING,
                'person'        => [
                    'id'       => $user?->id ?? 0, // @phpstan-ignore nullsafe.neverNull
                    'username' => $user?->name,
                ],
                'custom' => [
                    'correlationId' => correlationId(),
                ],
            ]);
        });

        $this->configureRateLimiting();

        $this->configureApiRateLimiting();
    }

    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        // Bind our custom rate limiter
        $this->app->extend(RateLimiter::class, fn($command, $app) => new CustomRateLimiter($app->make('cache')->driver(
            $app['config']->get('cache.limiter'),
        )));

        // trans_choice()'s plural rules don't know the `*_ai` locales - see AiLocaleMessageSelector
        $this->app->afterResolving('translator', static fn(Translator $translator) => $translator->setSelector(new AiLocaleMessageSelector()));
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('create-dungeonroute', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 100)->by($this->userKey($request)));
        RateLimiter::for('create-tag', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 60)->by($this->userKey($request)));
        RateLimiter::for('create-team', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 5)->by($this->userKey($request)));
        RateLimiter::for('create-reports', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 60)->by($this->userKey($request)));
        RateLimiter::for('create-user', function (Request $request) {
            // Bots somehow trigger /register?redirect=someurl a lot, so we have to catch it and not have them trigger the rate limiter
            // Besides, I only care about people creating new accounts, not "trying" to register
            if ($request->method() === 'GET') {
                return Limit::none();
            }

            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 50)->by($this->userKey($request));
        });

        // Heavy GET requests
        RateLimiter::for('search-dungeonroute', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 600)->by($this->userKey($request)));

        // This consumes the same resources as creating a route - so we limit it
        RateLimiter::for('mdt-details', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 1200)->by($this->userKey($request)));
        RateLimiter::for('mdt-export', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 1200)->by($this->userKey($request)));
        RateLimiter::for('simulate', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 120)->by($this->userKey($request)));
    }

    private function configureApiRateLimiting(): void
    {
        RateLimiter::for('api-general', fn(Request $request) => $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::$rateLimitOverridePerMinuteApi ?? 900)->by($this->userKey($request)));
        RateLimiter::for('api-combatlog-create-dungeonroute', fn(Request $request) => $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::$rateLimitOverridePerMinuteApi ?? 120)->by($this->userKey($request)));
        RateLimiter::for('api-combatlog-correct-event', fn(Request $request) => $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::$rateLimitOverridePerMinuteApi ?? 900)->by($this->userKey($request)));
        RateLimiter::for('api-patreon-diagnostics', fn(Request $request) => $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::$rateLimitOverridePerMinuteApi ?? 10)->by($this->userKey($request)));
        RateLimiter::for('api-create-dungeonroute-thumbnail', fn(Request $request) => $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::$rateLimitOverridePerMinuteApi ?? 30)->by($this->userKey($request)));
        // Full-table aggregate - modest cap, admins are exempt via noLimitForExemptionsApi so this really only bounds the ai_agent role
        RateLimiter::for('api-combatlog-observations-density', fn(Request $request) => $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::$rateLimitOverridePerMinuteApi ?? 20)->by($this->userKey($request)));
    }

    private function noLimitForExemptions(Request $request): ?Limit
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user?->hasRole(Role::roles(Role::ROLES_INTERNAL))) {
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
