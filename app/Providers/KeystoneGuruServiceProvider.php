<?php

namespace App\Providers;

use App\Models\DungeonRoute;
use App\Models\UserReport;
use App\Service\Cache\CacheService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Jenssegers\Agent\Agent;

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

        // Model helpers
        $this->app->bind('App\Service\Season\SeasonServiceInterface', 'App\Service\Season\SeasonService');
        $this->app->bind('App\Service\Expansion\ExpansionServiceInterface', 'App\Service\Expansion\ExpansionService');
        $this->app->bind('App\Service\Mapping\MappingServiceInterface', 'App\Service\Mapping\MappingService');

        // External communication
        $this->app->bind('App\Service\Discord\DiscordApiServiceInterface', 'App\Service\Discord\DiscordApiService');
        $this->app->bind('App\Service\Reddit\RedditApiServiceInterface', 'App\Service\Reddit\RedditApiService');
    }

    /**
     * Bootstrap services.
     *
     * @param CacheService $cacheService
     * @return void
     */
    public function boot(CacheService $cacheService)
    {
        view()->share('isMobile', (new Agent())->isMobile());
        view()->share('demoRoutes', DungeonRoute::where('demo', true)->where('published_state_id', 3)->orderBy('dungeon_id')->get());

        // Can use the Auth() global here!
        view()->composer('*', function ($view)
        {
            $view->with('numUserReports', Auth::check() && Auth::user()->is_admin ? UserReport::where('status', 0)->count() : 0);
        });
    }
}
