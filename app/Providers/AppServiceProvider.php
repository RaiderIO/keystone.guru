<?php

namespace App\Providers;

use App\Exceptions\Handler;
use App\Logic\Utils\IpAddress;
use App\Models\Feature\Feature;
use App\Models\Laratrust\Role;
use App\Models\User;
use App\Overrides\AiLocaleMessageSelector;
use App\Overrides\CustomRateLimiter;
use App\Overrides\EmptyTranslationFallbackTranslator;
use App\Overrides\TextareaEscapingHtml;
use App\Service\User\ApiTokenScope;
use Carbon\CarbonInterval;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rules\Password;
use Laratrust\Events\RoleAdded;
use Laratrust\Events\RoleRemoved;
use Laratrust\Events\RoleSynced;
use Laravel\Passport\Passport;
use Override;
use SocialiteProviders\Manager\SocialiteWasCalled;
use Spatie\Html\Html;

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

        // The one password policy for registration, password reset and password change
        Password::defaults(static fn(): Password => Password::min(8));

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
        Event::listen(
            [RoleAdded::class, RoleRemoved::class, RoleSynced::class],
            static function (RoleAdded|RoleRemoved|RoleSynced $event): void {
                if ($event->model instanceof User) {
                    Feature::forgetAllForUser($event->model);
                }
            },
        );

        $this->app->bind(ExceptionHandler::class, Handler::class);

        $this->configureRateLimiting();

        $this->configureApiRateLimiting();

        $this->configurePassport();
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

        // An empty translation falls back to en_US instead of rendering a blank - see EmptyTranslationFallbackTranslator
        $this->app->extend('translator', static fn(Translator $translator) => EmptyTranslationFallbackTranslator::fromTranslator($translator));

        // trans_choice()'s plural rules don't know the `*_ai` locales - see AiLocaleMessageSelector
        $this->app->afterResolving('translator', static fn(Translator $translator) => $translator->setSelector(new AiLocaleMessageSelector()));

        // html()->textarea() escapes its content - see TextareaEscapingHtml
        $this->app->extend(Html::class, static fn(Html $html, $app) => new TextareaEscapingHtml($app->make(Request::class)));

        // Must be set in register(): Passport reads it to register its routes and grants while it boots
        Passport::$deviceCodeGrantEnabled = false;
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('create-dungeonroute', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 100)->by($this->userKey($request)));
        // Every drag in the collaborative map editor is a write, and sandbox routes accept them without a session - the
        // ceiling sits above the whole site's busiest measured hour of editor writes (~1000), so only abuse reaches it
        RateLimiter::for('edit-dungeonroute', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 1200)->by($this->userKey($request)));
        // One call deletes up to a hundred routes, each costing a dozen-plus queries and its thumbnail files
        RateLimiter::for('delete-dungeonroutes', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 60)->by($this->userKey($request)));
        RateLimiter::for('create-tag', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 60)->by($this->userKey($request)));
        RateLimiter::for('create-api-token', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 20)->by($this->userKey($request)));
        RateLimiter::for('create-collection', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 30)->by($this->userKey($request)));
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
        // Every attempt costs a password hash comparison, and the per-username lockout of ThrottlesLogins does not
        // bound a caller that rotates usernames. Account access, so the ceiling is set well above what real traffic produces
        RateLimiter::for('login', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 120)->by($this->userKey($request)));
        // Every attempt is a guess at the signed-in account's current password, so this bounds how fast a stolen
        // session can find it and lock the owner out. No exemption for internal roles: their sessions are the ones
        // most worth guarding. The password form and the profile form (for an e-mail change) share one bucket
        RateLimiter::for('change-password', fn(Request $request) => Limit::perHour(self::$rateLimitOverrideHttp ?? 30)->by($this->userKey($request)));
        RateLimiter::for('reset-password', function (Request $request) {
            // Same as create-user: only the form submission is interesting, showing the form is free
            if ($request->method() === 'GET') {
                return Limit::none();
            }

            // Account recovery, so the ceiling is set well above what real traffic produces
            return $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 60)->by($this->userKey($request));
        });

        // Every OAuth callback (Google, Battle.net, Discord, Patreon) makes an outbound call to the provider. A sign-in is a
        // redirect plus a callback, so the ceiling sits well above what a person signing in or linking an account produces
        RateLimiter::for('oauth', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 60)->by($this->userKey($request)));

        // Writes a row per call and is reachable without a session, but it is only sent on two explicit user
        // actions - so the ceiling sits far above what the front-end can produce
        RateLimiter::for('store-metric', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 600)->by($this->userKey($request)));

        // Heavy GET requests
        RateLimiter::for('search-dungeonroute', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 600)->by($this->userKey($request)));
        // Every call is an outbound request to the Raider.IO API, and the front-end fires one per filter change
        RateLimiter::for('heatmap-data', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 600)->by($this->userKey($request)));

        // This consumes the same resources as creating a route - so we limit it
        RateLimiter::for('mdt-details', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 300)->by($this->userKey($request)));
        RateLimiter::for('mdt-export', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 600)->by($this->userKey($request)));
        RateLimiter::for('simulate', fn(Request $request) => $this->noLimitForExemptions($request) ?? Limit::perHour(self::$rateLimitOverrideHttp ?? 120)->by($this->userKey($request)));
    }

    private function configureApiRateLimiting(): void
    {
        RateLimiter::for('api-general', fn(Request $request) => $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::$rateLimitOverridePerMinuteApi ?? 900)->by($this->userKey($request)));
        RateLimiter::for('api-combatlog-create-dungeonroute', fn(Request $request) => $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::$rateLimitOverridePerMinuteApi ?? 120)->by($this->userKey($request)));
        RateLimiter::for('api-combatlog-correct-event', fn(Request $request) => $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::$rateLimitOverridePerMinuteApi ?? 900)->by($this->userKey($request)));
        RateLimiter::for('api-patreon-diagnostics', fn(Request $request) => $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::$rateLimitOverridePerMinuteApi ?? 10)->by($this->userKey($request)));
        // Each graph response carries a ~100-150 KB blob
        RateLimiter::for('api-walkgraphs', fn(Request $request) => $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::$rateLimitOverridePerMinuteApi ?? 30)->by($this->userKey($request)));
        RateLimiter::for('api-create-dungeonroute-thumbnail', fn(Request $request) => $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::$rateLimitOverridePerMinuteApi ?? 30)->by($this->userKey($request)));
        // The MCP endpoint answers every tool call; the per-tool write limits are enforced inside the tools
        RateLimiter::for('mcp', fn(Request $request) => Limit::perMinute(self::$rateLimitOverridePerMinuteApi ?? config('keystoneguru.api.mcp.requests_per_minute'))->by($this->userKey($request)));
        // Anyone may register an OAuth client, so this is keyed by IP address: there is no user yet
        RateLimiter::for('mcp-register', static fn(Request $request) => Limit::perHour(config('keystoneguru.api.mcp.client_registrations_per_hour'))->by(IpAddress::rateLimitKey($request->ip())));
        RateLimiter::for('api-combatlog-live-session-events', fn(Request $request) => $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::$rateLimitOverridePerMinuteApi ?? 900)->by($this->userKey($request)));
        // Full-table aggregate - modest cap, admins are exempt via noLimitForExemptionsApi so this really only bounds the ai_agent role
        RateLimiter::for('api-combatlog-observations-density', fn(Request $request) => $this->noLimitForExemptionsApi($request) ?? Limit::perMinute(self::$rateLimitOverridePerMinuteApi ?? 20)->by($this->userKey($request)));
    }

    private function noLimitForExemptions(Request $request): ?Limit
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user?->hasRole(Role::roles(Role::ROLES_EARLY_ACCESS))) {
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

        return $user?->id ?: IpAddress::rateLimitKey($request->ip());
    }

    private function configurePassport(): void
    {
        Passport::tokensCan(ApiTokenScope::passportScopes());

        Passport::tokensExpireIn(CarbonInterval::minutes(config('keystoneguru.api.authentication.access_token_lifetime_minutes')));
        Passport::refreshTokensExpireIn(CarbonInterval::days(config('keystoneguru.api.authentication.refresh_token_lifetime_days')));
        Passport::personalAccessTokensExpireIn(CarbonInterval::days(config('keystoneguru.api.authentication.personal_access_token_lifetime_days')));

        Passport::authorizationView('oauth.authorize');
    }
}
