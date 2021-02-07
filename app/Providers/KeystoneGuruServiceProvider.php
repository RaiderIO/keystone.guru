<?php

namespace App\Providers;

use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\PaidTier;
use App\Models\Release;
use App\Models\UserReport;
use App\Service\Cache\CacheService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Jenssegers\Agent\Agent;
use Tremby\LaravelGitVersion\GitVersionHelper;

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
        // Cache some variables so we don't continuously query data that never changes (unless there's a patch)
        $globalViewVariables = $cacheService->remember('global_view_variables', function ()
        {
            $demoRoutes = DungeonRoute::where('demo', true)->where('published_state_id', 3)->orderBy('dungeon_id')->get();
            return [
                'isProduction'      => config('app.env') === 'production',
                'demoRoutes'        => $demoRoutes,
                'demoRouteDungeons' => Dungeon::whereIn('id', $demoRoutes->pluck(['dungeon_id']))->get(),
                'latestReleaseId'   => Release::max('id')
            ];
        }, config('keystoneguru.cache.global_view_variables.ttl'));

        // All views
        view()->share('isMobile', (new Agent())->isMobile());
        view()->share('isProduction', $globalViewVariables['isProduction']);
        view()->share('demoRoutes', $globalViewVariables['demoRoutes']);


        // Can use the Auth() global here!
        view()->composer('*', function (View $view)
        {
            $view->with('numUserReports', Auth::check() && Auth::user()->is_admin ? UserReport::where('status', 0)->count() : 0);
            // Not logged in or not having paid for free ads will cause ads to come up
            $view->with('showAds', !Auth::check() || !Auth::user()->hasPaidTier(PaidTier::AD_FREE));
        });

        // Main view
        view()->composer('layouts.app', function (View $view) use ($globalViewVariables)
        {
            $view->with('version', GitVersionHelper::getVersion());
            $view->with('nameAndVersion', GitVersionHelper::getNameAndVersion());
            $view->with('hasNewChangelog', isset($_COOKIE['changelog_release']) ? $globalViewVariables['latestReleaseId'] > (int)$_COOKIE['changelog_release'] : true);
        });

        // Dungeon grid view
        view()->composer('common.dungeon.demoroutesgrid', function (View $view) use ($globalViewVariables)
        {
            $view->with('dungeons', $globalViewVariables['demoRouteDungeons']);
        });
    }
}
