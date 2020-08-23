<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class KeystoneGuruServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Bind the interface to the actual service
        $this->app->bind('App\Service\EchoServerHttpApiServiceInterface', 'App\Service\DiscordApiService');

        // Internals
        $this->app->bind('App\Service\Cache\CacheServiceInterface', 'App\Service\Cache\CacheService');

        // Dashboard
        $this->app->bind('App\Service\Dashboard\StatisticsServiceInterface', 'App\Service\Dashboard\UsersStatisticsService');
        $this->app->bind('App\Service\Dashboard\StatisticsServiceInterface', 'App\Service\Dashboard\TeamsStatisticsService');

        // M+ Seasons
        $this->app->bind('App\Service\Season\SeasonServiceInterface', 'App\Service\Season\SeasonService');

        // External communication
        $this->app->bind('App\Service\Discord\DiscordApiServiceInterface', 'App\Service\Discord\DiscordApiService');
        $this->app->bind('App\Service\Reddit\RedditApiServiceInterface', 'App\Service\Reddit\RedditApiService');
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
