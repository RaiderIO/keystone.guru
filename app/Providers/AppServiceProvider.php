<?php

namespace App\Providers;

use App\Overrides\CustomRateLimiter;
use Illuminate\Cache\RateLimiter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(!app()->isProduction());

        // Force HTTPS in production - these environments are running in AWS which terminates https at the load balancer
        // instead of at nginx, so the site will think it's serving http if it's not forced to https
        if(!$this->app->environment('local')) {
            URL::forceScheme('https');
        }

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
