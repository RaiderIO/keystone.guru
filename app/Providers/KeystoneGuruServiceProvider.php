<?php

namespace App\Providers;

use App\Logic\Utils\Counter;
use App\Logic\Utils\Stopwatch;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Season;
use App\Models\UserReport;
use App\Service\Cache\CacheService;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Cache\DevCacheService;
use App\Service\Discord\DiscordApiService;
use App\Service\Discord\DiscordApiServiceInterface;
use App\Service\DungeonRoute\CoverageService;
use App\Service\DungeonRoute\CoverageServiceInterface;
use App\Service\DungeonRoute\DevDiscoverService;
use App\Service\DungeonRoute\DiscoverService;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\DungeonRoute\ThumbnailService;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use App\Service\EchoServer\EchoServerHttpApiService;
use App\Service\EchoServer\EchoServerHttpApiServiceInterface;
use App\Service\Expansion\ExpansionData;
use App\Service\Expansion\ExpansionService;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\LiveSession\OverpulledEnemyService;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use App\Service\Mapping\MappingService;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\MDTExportStringService;
use App\Service\MDT\MDTExportStringServiceInterface;
use App\Service\MDT\MDTImportStringService;
use App\Service\MDT\MDTImportStringServiceInterface;
use App\Service\MDT\MDTMappingExportService;
use App\Service\MDT\MDTMappingExportServiceInterface;
use App\Service\MDT\MDTMappingImportService;
use App\Service\MDT\MDTMappingImportServiceInterface;
use App\Service\Npc\NpcService;
use App\Service\Npc\NpcServiceInterface;
use App\Service\Patreon\PatreonApiService;
use App\Service\Patreon\PatreonApiServiceInterface;
use App\Service\Patreon\PatreonService;
use App\Service\Patreon\PatreonServiceInterface;
use App\Service\Reddit\RedditApiService;
use App\Service\Reddit\RedditApiServiceInterface;
use App\Service\Season\SeasonService;
use App\Service\Season\SeasonServiceInterface;
use App\Service\SimulationCraft\RaidEventsService;
use App\Service\SimulationCraft\RaidEventsServiceInterface;
use App\Service\Subcreation\AffixGroupEaseTierService;
use App\Service\Subcreation\AffixGroupEaseTierServiceInterface;
use App\Service\Subcreation\SubcreationApiService;
use App\Service\Subcreation\SubcreationApiServiceInterface;
use App\Service\TimewalkingEvent\TimewalkingEventService;
use App\Service\TimewalkingEvent\TimewalkingEventServiceInterface;
use App\Service\View\ViewService;
use App\Service\View\ViewServiceInterface;
use App\Service\WowTools\WowToolsService;
use App\Service\WowTools\WowToolsServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
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
        $this->app->bind(EchoServerHttpApiServiceInterface::class, EchoServerHttpApiService::class);

        // Internals
        $this->app->bind(ThumbnailServiceInterface::class, ThumbnailService::class);
        $this->app->bind(RaidEventsServiceInterface::class, RaidEventsService::class);
        $this->app->bind(PatreonServiceInterface::class, PatreonService::class);
        $this->app->bind(MDTMappingExportServiceInterface::class, MDTMappingExportService::class);
        $this->app->bind(MDTMappingImportServiceInterface::class, MDTMappingImportService::class);

        // Model helpers
        if (config('app.env') === 'local') {
            $this->app->bind(CacheServiceInterface::class, DevCacheService::class);
            $this->app->bind(DiscoverServiceInterface::class, DevDiscoverService::class);
        } else {
            $this->app->bind(CacheServiceInterface::class, CacheService::class);
            $this->app->bind(DiscoverServiceInterface::class, DiscoverService::class);
        }
        $this->app->bind(ExpansionServiceInterface::class, ExpansionService::class);
        $this->app->bind(NpcServiceInterface::class, NpcService::class);

        // Depends on ExpansionService
        $this->app->bind(SeasonServiceInterface::class, SeasonService::class);
        $this->app->bind(OverpulledEnemyServiceInterface::class, OverpulledEnemyService::class);
        $this->app->bind(MappingServiceInterface::class, MappingService::class);
        $this->app->bind(AffixGroupEaseTierServiceInterface::class, AffixGroupEaseTierService::class);
        $this->app->bind(CoverageServiceInterface::class, CoverageService::class);

        // Depends on SeasonService
        $this->app->bind(TimewalkingEventServiceInterface::class, TimewalkingEventService::class);
        $this->app->bind(MDTImportStringServiceInterface::class, MDTImportStringService::class);
        $this->app->bind(MDTExportStringServiceInterface::class, MDTExportStringService::class);

        // Depends on all of the above - pretty much
        $this->app->bind(ViewServiceInterface::class, ViewService::class);

        // External communication
        $this->app->bind(DiscordApiServiceInterface::class, DiscordApiService::class);
        $this->app->bind(RedditApiServiceInterface::class, RedditApiService::class);
        $this->app->bind(SubcreationApiServiceInterface::class, SubcreationApiService::class);
        $this->app->bind(PatreonApiServiceInterface::class, PatreonApiService::class);
        $this->app->bind(WowToolsServiceInterface::class, WowToolsService::class);
    }

    /**
     * Bootstrap services.
     *
     * @param ViewServiceInterface $viewService
     * @param ExpansionServiceInterface $expansionService
     * @param AffixGroupEaseTierServiceInterface $affixGroupEaseTierService
     * @param MappingServiceInterface $mappingService
     * @return void
     */
    public function boot(
        ViewServiceInterface               $viewService,
        ExpansionServiceInterface          $expansionService,
        AffixGroupEaseTierServiceInterface $affixGroupEaseTierService,
        MappingServiceInterface            $mappingService
    )
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
                $adFree = Auth::check() && Auth::user()->hasPatreonBenefit(PatreonBenefit::AD_FREE);
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

            $view->with('currentSeason', $globalViewVariables['currentSeason']);
            $view->with('nextSeason', $globalViewVariables['nextSeason']);
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
            $view->with('currentSeason', $globalViewVariables['currentSeason']);
            $view->with('nextSeason', $globalViewVariables['nextSeason']);
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
                $currentAffixes[$expansionShortname] = optional($currentAffixGroupByRegion[$userOrDefaultRegion->short] ?? null)->id;
            }

            $view->with('currentSeason', $globalViewVariables['currentSeason']);
            $view->with('nextSeason', $globalViewVariables['nextSeason']);
            $view->with('currentAffixes', $currentAffixes);
            $view->with('allExpansions', $globalViewVariables['allExpansions']->pluck('id', 'shortname'));
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

        // Dungeon grid display
        view()->composer('common.dungeon.griddiscover', function (View $view) use ($globalViewVariables, $affixGroupEaseTierService) {
            /** @var AffixGroup|null $currentAffixGroup */
            $currentAffixGroup = $view->getData()['currentAffixGroup'];
            /** @var AffixGroup|null $nextAffixGroup */
            $nextAffixGroup = $view->getData()['nextAffixGroup'];

            $view->with('tiers', $affixGroupEaseTierService->getTiersByAffixGroups(collect([
                $currentAffixGroup,
                $nextAffixGroup,
            ])));
        });


        // Dungeon selector
        view()->composer('common.dungeon.select', function (View $view) use ($globalViewVariables) {
            $view->with('allExpansions', $globalViewVariables['allExpansions']);
            $view->with('allDungeons', $globalViewVariables['dungeonsByExpansionIdDesc']);
            $view->with('allActiveDungeons', $globalViewVariables['activeDungeonsByExpansionIdDesc']);
            $view->with('currentSeason', $globalViewVariables['currentSeason']);
            $view->with('nextSeason', $globalViewVariables['nextSeason']);
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

        view()->composer('common.dungeonroute.coverage.affixgroup', function (View $view) use ($globalViewVariables, $userOrDefaultRegion) {
            /** @var Collection|Dungeon[] $allActiveDungeons */
            $allActiveDungeons = $globalViewVariables['activeDungeonsByExpansionIdDesc'];

            /** @var Expansion $currentExpansion */
            $currentExpansion = $globalViewVariables['currentExpansion'];

            /** @var ExpansionData $expansionsData */
            $expansionsData = $globalViewVariables['expansionsData']->get($currentExpansion->shortname);

            /** @var Season $selectedSeason */
            $selectedSeason         = $globalViewVariables['currentSeason'];
            $cookieSelectedSeasonId = isset($_COOKIE['dungeonroute_coverage_season_id']) ? (int)$_COOKIE['dungeonroute_coverage_season_id'] : 0;

            if ($cookieSelectedSeasonId !== $globalViewVariables['currentSeason']->id &&
                $globalViewVariables['nextSeason'] !== null &&
                $cookieSelectedSeasonId === $globalViewVariables['nextSeason']->id) {
                $selectedSeason = $globalViewVariables['nextSeason'];
            }

            $view->with('currentSeason', $globalViewVariables['currentSeason']);
            $view->with('nextSeason', $globalViewVariables['nextSeason']);
            $view->with('selectedSeason', $selectedSeason);
            $view->with('currentAffixGroup', $selectedSeason->getCurrentAffixGroup());
            $view->with('affixgroups', $selectedSeason->affixgroups);
            $view->with('dungeons', $selectedSeason->dungeons);
        });

        // Maps
        view()->composer('common.maps.controls.pulls', function (View $view) use ($globalViewVariables) {
            $view->with('showAllEnabled', $_COOKIE['dungeon_speedrun_required_npcs_show_all'] ?? '0');
        });

        // Admin
        view()->composer('admin.dungeon.edit', function (View $view) use ($mappingService) {
            /** @var Dungeon|null $dungeon */
            $dungeon = $view->getData()['dungeon'] ?? null;
            $view->with('hasUnmergedMappingVersion', $dungeon && $mappingService->getDungeonsWithUnmergedMappingChanges()->has($dungeon->id));
        });

        // Team selector
        view()->composer('common.team.select', function (View $view) use ($globalViewVariables) {
            $view->with('teams', Auth::check() ? Auth::user()->teams : collect());
        });

        // Profile pages
        view()->composer('profile.edit', function (View $view) use ($globalViewVariables) {
            $view->with('allClasses', $globalViewVariables['characterClasses']);
            $view->with('allRegions', $globalViewVariables['allRegions']);
        });

        view()->composer(['profile.overview', 'common.dungeonroute.coverage.affixgroup'], function (View $view) use ($globalViewVariables) {
            $view->with('newRouteStyle', $_COOKIE['route_coverage_new_route_style'] ?? 'search');
        });

        // Custom blade directives
        $expressionToStringContentsParser = function ($expression, $callback) {
            $parameters = collect(explode(', ', $expression));

            foreach ($parameters as $parameter) {
                $callback(trim($parameter, '\'"'));
            }
        };

        Blade::directive('count', function ($expression) use ($expressionToStringContentsParser) {
            $expressionToStringContentsParser($expression, function ($parameter) {
                Counter::increase($parameter);
            });
        });

        Blade::directive('measure', function ($expression) use ($expressionToStringContentsParser) {
            $expressionToStringContentsParser($expression, function ($parameter) {
                Stopwatch::start($parameter);
            });
        });

        Blade::directive('endmeasure', function ($expression) use ($expressionToStringContentsParser) {
            $expressionToStringContentsParser($expression, function ($parameter) {
                Stopwatch::pause($parameter);
            });
        });
    }
}
