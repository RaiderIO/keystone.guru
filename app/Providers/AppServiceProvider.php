<?php

namespace App\Providers;

use App\Models\Release;
use App\Models\User;
use Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Monolog\Logger;
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
            'minimum_level' => Logger::WARNING,
            'person'        => [
                'id'       => optional($user)->id ?? 0,
                'username' => optional($user)->name,
            ],
            'custom'        => [
                'correlation_id' => correlationId(),
            ],
        ]);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
