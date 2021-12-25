<?php

namespace App\Providers;

use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\PaidTier;
use App\Models\UserReport;
use App\Service\Expansion\ExpansionData;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\Paginator;
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
        $this->app->bind('App\Service\EchoServerHttpApiServiceInterface', 'App\Service\EchoServerHttpApiService');

        // Internals

        // Model helpers
        if (config('app.env') === 'local') {
            $this->app->bind('App\Service\Cache\CacheServiceInterface', 'App\Service\Cache\DevCacheService');
            $this->app->bind('App\Service\DungeonRoute\DiscoverServiceInterface', 'App\Service\DungeonRoute\DevDiscoverService');
        } else {
            $this->app->bind('App\Service\Cache\CacheServiceInterface', 'App\Service\Cache\CacheService');
            $this->app->bind('App\Service\DungeonRoute\DiscoverServiceInterface', 'App\Service\DungeonRoute\DiscoverService');
        }
        $this->app->bind('App\Service\Expansion\ExpansionServiceInterface', 'App\Service\Expansion\ExpansionService');
        // Depends on ExpansionService
        $this->app->bind('App\Service\Season\SeasonServiceInterface', 'App\Service\Season\SeasonService');
        $this->app->bind('App\Service\LiveSession\OverpulledEnemyServiceInterface', 'App\Service\LiveSession\OverpulledEnemyService');
        $this->app->bind('App\Service\Mapping\MappingServiceInterface', 'App\Service\Mapping\MappingService');
        $this->app->bind('App\Service\Subcreation\AffixGroupEaseTierServiceInterface', 'App\Service\Subcreation\AffixGroupEaseTierService');
        // Depends on SeasonService
        $this->app->bind('App\Service\TimewalkingEvent\TimewalkingEventServiceInterface', 'App\Service\TimewalkingEvent\TimewalkingEventService');

        // Depends on all of the above - pretty much
        $this->app->bind('App\Service\View\ViewServiceInterface', 'App\Service\View\ViewService');

        // External communication
        $this->app->bind('App\Service\Discord\DiscordApiServiceInterface', 'App\Service\Discord\DiscordApiService');
        $this->app->bind('App\Service\Reddit\RedditApiServiceInterface', 'App\Service\Reddit\RedditApiService');
        $this->app->bind('App\Service\Subcreation\SubcreationApiServiceInterface', 'App\Service\Subcreation\SubcreationApiService');
    }

    /**
     * Bootstrap services.
     *
     * @param ViewServiceInterface $viewService
     * @param ExpansionServiceInterface $expansionService
     * @return void
     */
    public function boot(ViewServiceInterface $viewService, ExpansionServiceInterface $expansionService)
    {
        // There really is nothing here that's useful for console apps - migrations may fail trying to do the below anyways
        if (app()->runningInConsole()) {
            return;
        }

        // https://laravel.com/docs/8.x/upgrade#pagination
        Paginator::useBootstrap();

        // Cache some variables so we don't continuously query data that never changes (unless there's a patch)
        $globalViewVariables = $viewService->getCache();

        $userOrDefaultRegion = GameServerRegion::getUserOrDefaultRegion();


        // All views
        view()->share('isMobile', (new Agent())->isMobile());
        view()->share('isProduction', $globalViewVariables['isProduction']);
        view()->share('demoRoutes', $globalViewVariables['demoRoutes']);

        $isUserAdmin = null;
        $adFree      = null;
        // Can use the Auth() global here!
        view()->composer('*', function (View $view) use (&$isUserAdmin, &$adFree) {
            // Only set these once - then cache the result for any subsequent calls, don't perform these queries for ALL views
            if ($isUserAdmin === null) {
                $isUserAdmin = Auth::check() && Auth::getUser()->hasRole('admin');
            }

            if ($adFree === null) {
                $adFree = Auth::check() && Auth::user()->hasPaidTier(PaidTier::AD_FREE);
            }


            // Don't include the viewName in the layouts - they must inherit from whatever calls it!
            if (strpos($view->getName(), 'layouts') !== 0) {
                $view->with('viewName', $view->getName());
            }

            $view->with('theme', $_COOKIE['theme'] ?? 'darkly');
            $view->with('isUserAdmin', $isUserAdmin);
            $view->with('adFree', $adFree);
        });

        // Home page
        view()->composer('home', function (View $view) use ($globalViewVariables) {
            $view->with('userCount', $globalViewVariables['userCount']);
            $view->with('demoRouteDungeons', $globalViewVariables['demoRouteDungeons']);
            $view->with('demoRouteMapping', $globalViewVariables['demoRouteMapping']);
        });

        // Main view
        view()->composer(['layouts.app', 'layouts.sitepage', 'layouts.map', 'admin.dashboard.layouts.app'], function (View $view) use ($globalViewVariables) {
            $view->with('version', $globalViewVariables['appVersion']);
            $view->with('nameAndVersion', $globalViewVariables['appVersionAndName']);
            $view->with('latestRelease', $globalViewVariables['latestRelease']);
            $view->with('latestReleaseSpotlight', $globalViewVariables['latestReleaseSpotlight']);
        });

        view()->composer(['layouts.app', 'common.layout.footer'], function (View $view) use ($globalViewVariables) {
            $view->with('hasNewChangelog', isset($_COOKIE['changelog_release']) ? $globalViewVariables['latestRelease']->id > (int)$_COOKIE['changelog_release'] : false);
        });

        view()->composer('common.layout.navuser', function (View $view) use ($isUserAdmin) {
            $view->with('numUserReports', $isUserAdmin ? UserReport::where('status', 0)->count() : 0);
        });

        view()->composer('common.layout.header', function (View $view) use ($globalViewVariables) {
            $view->with('activeExpansions', $globalViewVariables['activeExpansions']);
        });

        view()->composer([
            'dungeonroute.discover.category',
            'dungeonroute.discover.dungeon.category',
            'misc.affixes',
            'dungeonroute.discover.discover',
            'dungeonroute.discover.dungeon.overview',
        ], function (View $view) use ($globalViewVariables, $expansionService, $userOrDefaultRegion) {
            /** @var Expansion $expansion */
            $expansion = $view->getData()['expansion'];

            /** @var ExpansionData $expansionsData */
            $expansionsData = $globalViewVariables['expansionsData']->get($expansion->shortname);

            $view->with('currentAffixGroup', $expansionsData->getExpansionSeason()->getAffixGroups()->getCurrentAffixGroup($userOrDefaultRegion));
            $view->with('nextAffixGroup', $expansionsData->getExpansionSeason()->getAffixGroups()->getNextAffixGroup($userOrDefaultRegion));
        });

        // Dungeon grid view
        view()->composer('dungeonroute.discover.search', function (View $view) use ($globalViewVariables) {
            $view->with('currentExpansion', $globalViewVariables['currentExpansion']);
            $view->with('allAffixGroupsByActiveExpansion', $globalViewVariables['allAffixGroupsByActiveExpansion']);
            $view->with('featuredAffixesByActiveExpansion', $globalViewVariables['featuredAffixesByActiveExpansion']);
            $view->with('activeExpansions', $globalViewVariables['activeExpansions']);
        });

        view()->composer(['common.forms.oauth', 'common.forms.register'], function (View $view) use ($globalViewVariables) {
            $view->with('allRegions', $globalViewVariables['allRegions']);
        });

        // Displaying a release
        view()->composer('common.release.release', function (View $view) use ($globalViewVariables) {
            $view->with('categories', $globalViewVariables['releaseChangelogCategories']);
        });

        // Displaying affixes
        view()->composer('common.group.affixes', function (View $view) use ($globalViewVariables, $userOrDefaultRegion) {
            // Convert the current affixes list for ALL regions, and just take the ones that the current user's region has
            $currentAffixes = [];
            foreach ($globalViewVariables['allCurrentAffixes'] as $expansionShortname => $currentAffixGroupByRegion) {
                $currentAffixes[$expansionShortname] = $currentAffixGroupByRegion[$userOrDefaultRegion->short]->id;
            }

            $view->with('currentAffixes', $currentAffixes);
            $view->with('dungeonExpansions', $globalViewVariables['dungeonExpansions']);
            $view->with('allAffixGroups', $globalViewVariables['allAffixGroups']);
            $view->with('affixes', $globalViewVariables['affixes']);
            $view->with('expansionsData', $globalViewVariables['expansionsData']);
        });

        // Displaying a release
        view()->composer('common.group.composition', function (View $view) use ($globalViewVariables) {
            $view->with('specializations', $globalViewVariables['characterClassSpecializations']);
            $view->with('classes', $globalViewVariables['characterClasses']);
            $view->with('racesClasses', $globalViewVariables['characterRacesClasses']);
            $view->with('allFactions', $globalViewVariables['allFactions']);
        });

        // Dungeon selector
        view()->composer('common.dungeon.select', function (View $view) use ($globalViewVariables) {
            $view->with('allExpansions', $globalViewVariables['allExpansions']);
            $view->with('allDungeons', $globalViewVariables['dungeonsByExpansionIdDesc']);
            $view->with('allActiveDungeons', $globalViewVariables['activeDungeonsByExpansionIdDesc']);
            $view->with('siegeOfBoralus', $globalViewVariables['siegeOfBoralus']);
        });

        // Dungeonroute attributes selector, Dungeonroute table
        view()->composer(['common.dungeonroute.attributes', 'common.dungeonroute.table'], function (View $view) use ($globalViewVariables) {
            $view->with('allRouteAttributes', $globalViewVariables['allRouteAttributes']);
        });

        view()->composer('common.dungeonroute.publish', function (View $view) use ($globalViewVariables) {
            $view->with('allPublishedStates', $globalViewVariables['allPublishedStates']);
        });

        view()->composer('common.dungeonroute.tier', function (View $view) use ($globalViewVariables) {
            $view->with('affixGroupEaseTiersByAffixGroup', $globalViewVariables['affixGroupEaseTiersByAffixGroup']);
        });

        // Team selector
        view()->composer('common.team.select', function (View $view) use ($globalViewVariables) {
            $view->with('teams', Auth::check() ? Auth::user()->teams : []);
        });

        // Profile pages
        view()->composer('profile.edit', function (View $view) use ($globalViewVariables) {
            $view->with('allClasses', $globalViewVariables['characterClasses']);
            $view->with('allRegions', $globalViewVariables['allRegions']);
        });
    }
}
