<?php

namespace App\Providers;

use App\Models\Release;
use App\Models\User;
use App\Overrides\CustomRateLimiter;
use Auth;
use Illuminate\Cache\RateLimiter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Rollbar\Payload\Level;
use Rollbar\Rollbar;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(!app()->isProduction());

        /** @var User|null $user */
        $user = Auth::user();

        // https://docs.rollbar.com/docs/php-configuration-reference
        Rollbar::init([
            // I don't care about rollbar when developing locally
            'enabled'       => !app()->isLocal(),
            'access_token'  => config('keystoneguru.rollbar.server_access_token'),
            'environment'   => config('app.env'),
            'root'          => base_path(),
            // @TODO I don't like this query here
            'code_version'  => Release::latest()->first()->version,
            'minimum_level' => Level::WARNING,
            'person'        => [
                'id'       => optional($user)->id ?? 0,
                'username' => optional($user)->name,
            ],
            'custom'        => [
                'correlationId' => correlationId(),
            ],
        ]);

        // Ensure that we know the original IP address that made the request
        // https://khalilst.medium.com/get-real-client-ip-behind-cloudflare-in-laravel-189cb89059ff
        Request::setTrustedProxies(
            ['REMOTE_ADDR'],
            Request::HEADER_X_FORWARDED_FOR
        );
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind our custom rate limiter
        $this->app->extend(RateLimiter::class, function ($command, $app) {
            return new CustomRateLimiter($app->make('cache')->driver(
                $app['config']->get('cache.limiter')
            ));
        });
    }
}
