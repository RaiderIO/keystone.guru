<?php

namespace App\Providers;

use App\Models\Release;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Rollbar\Rollbar;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(!app()->isProduction());

        Rollbar::init([
            'access_token' => config('keystoneguru.rollbar.server_access_token'),
            'environment'  => config('app.env'),
            // @TODO I don't like this query here
            'code_version' => Release::latest()->first()->version,
        ]);

        // I don't care about rollbar when developing locally
        if (app()->isLocal()) {
            Rollbar::disable();
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
