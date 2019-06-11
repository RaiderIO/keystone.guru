<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class EchoServerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Bind the interface to the actual service
        $this->app->bind('App\Service\EchoServerConfigServiceInterface', 'App\Service\EchoServerConfigService');
        $this->app->bind('App\Service\EchoServerHttpApiServiceInterface', 'App\Service\EchoServerHttpApiService');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
