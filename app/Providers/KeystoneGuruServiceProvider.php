<?php

namespace App\Providers;

use App\Logic\Utils\Counter;
use App\Logic\Utils\Stopwatch;
use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Release;
use App\Models\Season;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Models\User;
use App\Models\UserReport;
use App\Service\AdProvider\AdProviderService;
use App\Service\AdProvider\AdProviderServiceInterface;
use App\Service\AffixGroup\AffixGroupEaseTierService;
use App\Service\AffixGroup\AffixGroupEaseTierServiceInterface;
use App\Service\AffixGroup\ArchonApiService;
use App\Service\AffixGroup\ArchonApiServiceInterface;
use App\Service\Cache\CacheService;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Cache\DevCacheService;
use App\Service\CombatLog\CombatLogDataExtractionService;
use App\Service\CombatLog\CombatLogDataExtractionServiceInterface;
use App\Service\CombatLog\CombatLogMappingVersionService;
use App\Service\CombatLog\CombatLogMappingVersionServiceInterface;
use App\Service\CombatLog\CombatLogService;
use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\CombatLogSplitService;
use App\Service\CombatLog\CombatLogSplitServiceInterface;
use App\Service\CombatLog\CreateRouteDungeonRouteService;
use App\Service\CombatLog\CreateRouteDungeonRouteServiceInterface;
use App\Service\CombatLog\ResultEventDungeonRouteService;
use App\Service\CombatLog\ResultEventDungeonRouteServiceInterface;
use App\Service\Coordinates\CoordinatesService;
use App\Service\Coordinates\CoordinatesServiceInterface;
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
use App\Service\GameVersion\GameVersionService;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\LiveSession\OverpulledEnemyService;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use App\Service\MapContext\MapContextService;
use App\Service\MapContext\MapContextServiceInterface;
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
use App\Service\Metric\MetricService;
use App\Service\Metric\MetricServiceInterface;
use App\Service\Npc\NpcService;
use App\Service\Npc\NpcServiceInterface;
use App\Service\Patreon\PatreonApiService;
use App\Service\Patreon\PatreonApiServiceInterface;
use App\Service\Patreon\PatreonService;
use App\Service\Patreon\PatreonServiceInterface;
use App\Service\ReadOnlyMode\ReadOnlyModeService;
use App\Service\ReadOnlyMode\ReadOnlyModeServiceInterface;
use App\Service\Reddit\RedditApiService;
use App\Service\Reddit\RedditApiServiceInterface;
use App\Service\Season\SeasonService;
use App\Service\Season\SeasonServiceInterface;
use App\Service\SimulationCraft\RaidEventsService;
use App\Service\SimulationCraft\RaidEventsServiceInterface;
use App\Service\StructuredLogging\StructuredLoggingService;
use App\Service\StructuredLogging\StructuredLoggingServiceInterface;
use App\Service\TimewalkingEvent\TimewalkingEventService;
use App\Service\TimewalkingEvent\TimewalkingEventServiceInterface;
use App\Service\User\UserService;
use App\Service\User\UserServiceInterface;
use App\Service\View\ViewService;
use App\Service\View\ViewServiceInterface;
use App\Service\Wowhead\WowheadService;
use App\Service\Wowhead\WowheadServiceInterface;
use App\Service\WowTools\WowToolsService;
use App\Service\WowTools\WowToolsServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Jenssegers\Agent\Agent;

class KeystoneGuruServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the interface to the actual service
        $this->app->bind(EchoServerHttpApiServiceInterface::class, EchoServerHttpApiService::class);

        // Internals
        $this->app->bind(CoordinatesServiceInterface::class, CoordinatesService::class);
        $this->app->bind(ThumbnailServiceInterface::class, ThumbnailService::class);
        $this->app->bind(PatreonServiceInterface::class, PatreonService::class);
        $this->app->bind(MDTMappingExportServiceInterface::class, MDTMappingExportService::class);
        $this->app->bind(MDTMappingImportServiceInterface::class, MDTMappingImportService::class);
        $this->app->bind(MetricServiceInterface::class, MetricService::class);
        $this->app->bind(CombatLogServiceInterface::class, CombatLogService::class);
        $this->app->bind(CombatLogDataExtractionServiceInterface::class, CombatLogDataExtractionService::class);
        $this->app->bind(CombatLogSplitServiceInterface::class, CombatLogSplitService::class);
        $this->app->bind(CombatLogMappingVersionServiceInterface::class, CombatLogMappingVersionService::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(GameVersionServiceInterface::class, GameVersionService::class);
        $this->app->bind(StructuredLoggingServiceInterface::class, StructuredLoggingService::class);

        // Depends on CoordinatesService
        $this->app->bind(RaidEventsServiceInterface::class, RaidEventsService::class);

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

        // Depends on CacheService
        $this->app->bind(ReadOnlyModeServiceInterface::class, ReadOnlyModeService::class);

        // Depends on ExpansionService
        $this->app->bind(SeasonServiceInterface::class, SeasonService::class);
        $this->app->bind(OverpulledEnemyServiceInterface::class, OverpulledEnemyService::class);
        $this->app->bind(MappingServiceInterface::class, MappingService::class);
        $this->app->bind(CoverageServiceInterface::class, CoverageService::class);

        // Depends on SeasonService
        $this->app->bind(AffixGroupEaseTierServiceInterface::class, AffixGroupEaseTierService::class);

        // Depends on CacheService, CoordinatesService, OverpulledEnemyService
        $this->app->bind(MapContextServiceInterface::class, MapContextService::class);

        // Depends on SeasonService, CacheService, CoordinatesService
        $this->app->bind(TimewalkingEventServiceInterface::class, TimewalkingEventService::class);
        $this->app->bind(MDTImportStringServiceInterface::class, MDTImportStringService::class);
        $this->app->bind(MDTExportStringServiceInterface::class, MDTExportStringService::class);

        // Depends on CombatLogService, SeasonService, CoordinatesService
        $this->app->bind(CreateRouteDungeonRouteServiceInterface::class, CreateRouteDungeonRouteService::class);
        $this->app->bind(ResultEventDungeonRouteServiceInterface::class, ResultEventDungeonRouteService::class);

        // Depends on all of the above - pretty much
        $this->app->bind(ViewServiceInterface::class, ViewService::class);

        // External communication
        $this->app->bind(DiscordApiServiceInterface::class, DiscordApiService::class);
        $this->app->bind(RedditApiServiceInterface::class, RedditApiService::class);
        $this->app->bind(ArchonApiServiceInterface::class, ArchonApiService::class);
        $this->app->bind(PatreonApiServiceInterface::class, PatreonApiService::class);
        $this->app->bind(WowToolsServiceInterface::class, WowToolsService::class);
        $this->app->bind(AdProviderServiceInterface::class, AdProviderService::class);
        $this->app->bind(WowheadServiceInterface::class, WowheadService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(
        ViewServiceInterface               $viewService,
        ExpansionServiceInterface          $expansionService,
        AffixGroupEaseTierServiceInterface $affixGroupEaseTierService,
        MappingServiceInterface            $mappingService,
        GameVersionServiceInterface        $gameVersionService
    ): void {
        // There really is nothing here that's useful for console apps - migrations may fail trying to do the below anyway
        if (!app()->runningUnitTests()) {
            if (app()->runningInConsole()) {
                return;
            }

            session_set_cookie_params([
                'secure'   => true,
                'httponly' => false,
                'samesite' => 'None',
            ]);
        }

        // https://laravel.com/docs/8.x/upgrade#pagination
        Paginator::useBootstrap();

        // Cache some variables, so we don't continuously query data that never changes (unless there's a patch)
        $globalViewVariables = $viewService->getGlobalViewVariables();

        // All views
        view()->share('isMobile', (new Agent())->isMobile());
        view()->share('isLocal', $globalViewVariables['isLocal']);
        view()->share('isMapping', $globalViewVariables['isMapping']);
        view()->share('isProduction', $globalViewVariables['isProduction']);
        view()->share('demoRoutes', $globalViewVariables['demoRoutes']);

        $isUserAdmin            = null;
        $adFree                 = null;
        $userOrDefaultRegion    = null;
        $currentUserGameVersion = null;
        $currentExpansion       = null;

        // Can use the Auth() global here!
        view()->composer('*', static function (View $view) use ($gameVersionService, $expansionService, &$isUserAdmin, &$adFree, &$userOrDefaultRegion, &$currentUserGameVersion, &$currentExpansion) {
            // Only set these once - then cache the result for any subsequent calls, don't perform these queries for ALL views
            /** @var User|null $user */
            $user = Auth::getUser();
            if ($isUserAdmin === null) {
                $isUserAdmin = optional($user)->hasRole('admin');
            }
            if ($adFree === null) {
                $adFree = optional($user)->hasPatreonBenefit(PatreonBenefit::AD_FREE) ||
                    optional($user)->hasAdFreeGiveaway();
            }
            $userOrDefaultRegion    ??= GameServerRegion::getUserOrDefaultRegion();
            $currentUserGameVersion ??= $gameVersionService->getGameVersion($user);
            $currentExpansion       ??= $expansionService->getCurrentExpansion($userOrDefaultRegion);
            // Don't include the viewName in the layouts - they must inherit from whatever calls it!
            if (!str_starts_with((string)$view->getName(), 'layouts')) {
                $view->with('viewName', $view->getName());
            }
            $view->with('theme', $_COOKIE['theme'] ?? 'darkly');
            $view->with('isUserAdmin', $isUserAdmin);
            $view->with('adFree', $adFree);
            $view->with('userOrDefaultRegion', $userOrDefaultRegion);
            $view->with('currentUserGameVersion', $currentUserGameVersion);
        });

        // Home page
        view()->composer('home', static function (View $view) use ($viewService, $globalViewVariables, &$userOrDefaultRegion) {
            $view->with('userCount', $globalViewVariables['userCount']);
            $view->with('demoRouteDungeons', $globalViewVariables['demoRouteDungeons']);
            $view->with('demoRouteMapping', $globalViewVariables['demoRouteMapping']);
            $userOrDefaultRegion ??= GameServerRegion::getUserOrDefaultRegion();
            $regionViewVariables = $viewService->getGameServerRegionViewVariables($userOrDefaultRegion);
            $view->with('currentSeason', $regionViewVariables['currentSeason']);
        });

        // Main view
        view()->composer(['layouts.app', 'layouts.sitepage', 'layouts.map', 'admin.dashboard.layouts.app'],
            static function (View $view) use ($globalViewVariables) {
                $view->with('version', $globalViewVariables['appVersion']);
                $view->with('revision', $globalViewVariables['appRevision']);
                $view->with('nameAndVersion', $globalViewVariables['appVersionAndName']);
                $view->with('latestRelease', $globalViewVariables['latestRelease']);
                $view->with('latestReleaseSpotlight', $globalViewVariables['latestReleaseSpotlight']);
            });

        view()->composer(['layouts.app', 'common.layout.footer'], static function (View $view) use ($globalViewVariables) {
            $view->with('hasNewChangelog',
                isset($_COOKIE['changelog_release']) && $globalViewVariables['latestRelease']->id > (int)$_COOKIE['changelog_release']);
        });

        view()->composer(['common.layout.header', 'common.layout.navgameversions'], static function (View $view) use ($globalViewVariables) {
            $view->with('allGameVersions', $globalViewVariables['allGameVersions']);
        });

        view()->composer('common.layout.navuser', static function (View $view) use ($isUserAdmin) {
            $view->with('numUserReports', $isUserAdmin ? UserReport::where('status', 0)->count() : 0);
        });

        view()->composer('common.layout.header', static function (View $view) use ($viewService, $globalViewVariables, &$userOrDefaultRegion) {
            $view->with('activeExpansions', $globalViewVariables['activeExpansions']);
            $userOrDefaultRegion ??= GameServerRegion::getUserOrDefaultRegion();
            $regionViewVariables = $viewService->getGameServerRegionViewVariables($userOrDefaultRegion);
            $view->with('currentSeason', $regionViewVariables['currentSeason']);
            $view->with('nextSeason', $regionViewVariables['nextSeason']);
        });

        view()->composer([
            'dungeonroute.discover.category',
            'dungeonroute.discover.dungeon.category',
            'dungeonroute.discover.season.category',
            'misc.affixes',
            'dungeonroute.discover.discover',
            'dungeonroute.discover.dungeon.overview',
            'dungeonroute.discover.season.overview',
        ], static function (View $view) use ($viewService, &$userOrDefaultRegion) {
            /** @var Expansion $expansion */
            $expansion           = $view->getData()['expansion'];
            $userOrDefaultRegion ??= GameServerRegion::getUserOrDefaultRegion();
            $regionViewVariables = $viewService->getGameServerRegionViewVariables($userOrDefaultRegion);
            /** @var ExpansionData $expansionsData */
            $expansionsData = $regionViewVariables['expansionsData']->get($expansion->shortname);
            $view->with('currentAffixGroup', $expansionsData->getExpansionSeason()->getAffixGroups()->getCurrentAffixGroup());
            $view->with('nextAffixGroup', $expansionsData->getExpansionSeason()->getAffixGroups()->getNextAffixGroup());
        });

        // Dungeon grid view
        view()->composer('dungeonroute.discover.search', static function (View $view) use ($viewService, &$userOrDefaultRegion) {
            $userOrDefaultRegion ??= GameServerRegion::getUserOrDefaultRegion();
            $regionViewVariables = $viewService->getGameServerRegionViewVariables($userOrDefaultRegion);
            $view->with('currentExpansion', $regionViewVariables['currentExpansion']);
            $view->with('allAffixGroupsByActiveExpansion', $regionViewVariables['allAffixGroupsByActiveExpansion']);
            $view->with('featuredAffixesByActiveExpansion', $regionViewVariables['featuredAffixesByActiveExpansion']);
            $view->with('currentSeason', $regionViewVariables['currentSeason']);
            $view->with('nextSeason', $regionViewVariables['nextSeason']);
        });

        view()->composer('common.dungeonroute.create.dungeondifficultyselect', static function (View $view) use ($globalViewVariables) {
            $view->with('allSpeedrunDungeons', $globalViewVariables['allSpeedrunDungeons']);
        });

        view()->composer(['common.forms.oauth', 'common.forms.register'], static function (View $view) use ($globalViewVariables) {
            $view->with('allRegions', $globalViewVariables['allRegions']);
        });

        view()->composer(['common.forms.createroute', 'common.forms.createtemporaryroute'], static function (View $view) {
            $routeKeyLevelDefault = '10;15';
            $routeKeyLevel        = $_COOKIE['route_key_level'] ?? $routeKeyLevelDefault;
            $explode              = explode(';', $routeKeyLevel);
            if (count($explode) !== 2) {
                $routeKeyLevel = $routeKeyLevelDefault;
                $explode       = explode(';', $routeKeyLevel);
            }

            $view->with('routeKeyLevelFrom', $explode[0]);
            $view->with('routeKeyLevelTo', $explode[1]);
        });

        // Displaying a release
        view()->composer('common.release.release', static function (View $view) use ($globalViewVariables) {
            $view->with('categories', $globalViewVariables['releaseChangelogCategories']);
        });

        // Displaying affixes
        view()->composer('common.group.affixes', static function (View $view) use ($viewService, $globalViewVariables, &$userOrDefaultRegion) {
            $userOrDefaultRegion ??= GameServerRegion::getUserOrDefaultRegion();
            $regionViewVariables = $viewService->getGameServerRegionViewVariables($userOrDefaultRegion);
            $view->with('allExpansions', $globalViewVariables['allExpansions']->pluck('id', 'shortname'));
            $view->with('dungeonExpansions', $globalViewVariables['dungeonExpansions']);
            $view->with('affixes', $globalViewVariables['allAffixes']);
            $view->with('currentSeason', $regionViewVariables['currentSeason']);
            $view->with('nextSeason', $regionViewVariables['nextSeason']);
            $view->with('allAffixGroups', $regionViewVariables['allAffixGroups']);
            $view->with('expansionsData', $regionViewVariables['expansionsData']);
            $view->with('currentAffixes', $regionViewVariables['allCurrentAffixes']);
        });

        // Displaying a release
        view()->composer('common.group.composition', static function (View $view) use ($globalViewVariables) {
            $view->with('specializations', $globalViewVariables['characterClassSpecializations']);
            $view->with('classes', $globalViewVariables['characterClasses']);
            $view->with('racesClasses', $globalViewVariables['characterRacesClasses']);
            $view->with('allFactions', $globalViewVariables['allFactions']);
        });

        // Dungeon grid display
        view()->composer('common.dungeon.gridtabs', static function (View $view) use ($viewService, $globalViewVariables, &$userOrDefaultRegion) {
            $userOrDefaultRegion ??= GameServerRegion::getUserOrDefaultRegion();
            $regionViewVariables = $viewService->getGameServerRegionViewVariables($userOrDefaultRegion);
            $view->with('activeExpansions', $globalViewVariables['activeExpansions']);
            $view->with('currentSeason', $regionViewVariables['currentSeason']);
            $view->with('nextSeason', $regionViewVariables['nextSeason']);
        });

        view()->composer('common.dungeon.griddiscover', static function (View $view) use ($affixGroupEaseTierService) {
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
        view()->composer('common.dungeon.select', static function (View $view) use ($viewService, $globalViewVariables, &$userOrDefaultRegion) {
            $userOrDefaultRegion ??= GameServerRegion::getUserOrDefaultRegion();
            $regionViewVariables = $viewService->getGameServerRegionViewVariables($userOrDefaultRegion);
            $view->with('currentSeason', $regionViewVariables['currentSeason']);
            $view->with('nextSeason', $regionViewVariables['nextSeason']);
            $view->with('allExpansions', $globalViewVariables['allExpansions']);
            $view->with('allDungeons', $globalViewVariables['dungeonsByExpansionIdDesc']);
            $view->with('allActiveDungeons', $globalViewVariables['activeDungeonsByExpansionIdDesc']);
            $view->with('siegeOfBoralus', $globalViewVariables['siegeOfBoralus']);
        });

        // Dungeonroute attributes selector, Dungeonroute table
        view()->composer(['common.dungeonroute.attributes', 'common.dungeonroute.table'], static function (View $view) use ($globalViewVariables) {
            $view->with('allRouteAttributes', $globalViewVariables['allRouteAttributes']);
        });

        view()->composer('common.dungeonroute.publish', static function (View $view) use ($globalViewVariables) {
            $view->with('allPublishedStates', $globalViewVariables['allPublishedStates']);
        });

        view()->composer('common.dungeonroute.tier', static function (View $view) use ($globalViewVariables) {
            $view->with('affixGroupEaseTiersByAffixGroup', $globalViewVariables['affixGroupEaseTiersByAffixGroup']);
        });

        view()->composer('common.dungeonroute.coverage.affixgroup', static function (View $view) use ($viewService, &$userOrDefaultRegion) {
            $userOrDefaultRegion ??= GameServerRegion::getUserOrDefaultRegion();
            $regionViewVariables = $viewService->getGameServerRegionViewVariables($userOrDefaultRegion);
            /** @var Season $selectedSeason */
            $selectedSeason         = $regionViewVariables['currentSeason'];
            $cookieSelectedSeasonId = isset($_COOKIE['dungeonroute_coverage_season_id']) ? (int)$_COOKIE['dungeonroute_coverage_season_id'] : 0;
            if ($cookieSelectedSeasonId !== $regionViewVariables['currentSeason']->id &&
                $regionViewVariables['nextSeason'] !== null &&
                $cookieSelectedSeasonId === $regionViewVariables['nextSeason']->id) {
                $selectedSeason = $regionViewVariables['nextSeason'];
            }
            $view->with('currentSeason', $regionViewVariables['currentSeason']);
            $view->with('nextSeason', $regionViewVariables['nextSeason']);
            $view->with('selectedSeason', $selectedSeason);
            $view->with('currentAffixGroup', $selectedSeason->getCurrentAffixGroup());
            $view->with('affixgroups', $selectedSeason->affixgroups);
            $view->with('dungeons', $selectedSeason->dungeons);
        });

        // Maps
        view()->composer('common.maps.controls.pulls', static function (View $view) {
            $view->with('showAllEnabled', $_COOKIE['dungeon_speedrun_required_npcs_show_all'] ?? '0');
        });

        view()->composer('common.maps.controls.pullsworkbench', static function (View $view) use ($globalViewVariables) {
            $view->with('spellsSelect', $globalViewVariables['selectableSpellsByCategory']);
        });

        // Admin
        view()->composer('admin.dungeon.edit', static function (View $view) use ($mappingService) {
            /** @var Dungeon|null $dungeon */
            $dungeon = $view->getData()['dungeon'] ?? null;
            $view->with('hasUnmergedMappingVersion', $dungeon && $mappingService->getDungeonsWithUnmergedMappingChanges()->has($dungeon->id));
        });

        // Team selector
        view()->composer('common.team.select', static function (View $view) {
            $view->with('teams', Auth::check() ? Auth::user()->teams : collect());
        });

        // Simulation
        view()->composer('common.modal.simulate', static function (View $view) use ($viewService, &$userOrDefaultRegion) {
            $userOrDefaultRegion ??= GameServerRegion::getUserOrDefaultRegion();
            $regionViewVariables = $viewService->getGameServerRegionViewVariables($userOrDefaultRegion);
            /** @var Season $currentSeason */
            $currentSeason     = $regionViewVariables['currentSeason'];
            $currentAffixGroup = $currentSeason->getCurrentAffixGroup();
            $view->with('isThundering', $currentAffixGroup?->hasAffix(Affix::AFFIX_THUNDERING) ?? false);
        });

        view()->composer('common.modal.simulateoptions.default', static function (View $view) use ($viewService, &$userOrDefaultRegion) {
            $userOrDefaultRegion ??= GameServerRegion::getUserOrDefaultRegion();
            $regionViewVariables = $viewService->getGameServerRegionViewVariables($userOrDefaultRegion);
            $shroudedBountyTypes = [];
            foreach (SimulationCraftRaidEventsOptions::ALL_SHROUDED_BOUNTY_TYPES as $bountyType) {
                $shroudedBountyTypes[$bountyType] = __(sprintf('view_common.modal.simulate.shrouded_bounty_types.%s', $bountyType));
            }
            $affixes = [];
            foreach (SimulationCraftRaidEventsOptions::ALL_AFFIXES as $affix) {
                $affixes[$affix] = __(sprintf('view_common.modal.simulate.affixes.%s', $affix));
            }
            /** @var Season $currentSeason */
            $currentSeason     = $regionViewVariables['currentSeason'];
            $currentAffixGroup = $currentSeason->getCurrentAffixGroup();
            $view->with('shroudedBountyTypes', $shroudedBountyTypes);
            $view->with('affixes', $affixes);
            $view->with('isShrouded', $currentAffixGroup?->hasAffix(Affix::AFFIX_SHROUDED) ?? false);
        });

        // Thirdparty
        view()->composer('common.thirdparty.rollbar.rollbar', static function (View $view) use ($globalViewVariables) {
            /** @var Release $latestRelease */
            $latestRelease = $globalViewVariables['latestRelease'];
            $view->with('latestRelease', $latestRelease);
        });

        // Profile pages
        view()->composer('profile.edit', static function (View $view) use ($globalViewVariables) {
            $view->with('allClasses', $globalViewVariables['characterClasses']);
            $view->with('allRegions', $globalViewVariables['allRegions']);
        });

        view()->composer(['profile.overview', 'common.dungeonroute.coverage.affixgroup'], static function (View $view) {
            $view->with('newRouteStyle', $_COOKIE['route_coverage_new_route_style'] ?? 'search');
        });

        // Custom blade directives
        $expressionToStringContentsParser = static function ($expression, $callback) {
            $parameters = collect(explode(', ', $expression));
            foreach ($parameters as $parameter) {
                $callback(trim($parameter, '\'"'));
            }
        };

        Blade::directive('count', static function ($expression) use ($expressionToStringContentsParser) {
            $expressionToStringContentsParser($expression, static function ($parameter) {
                Counter::increase($parameter);
            });
        });

        Blade::directive('measure', static function ($expression) use ($expressionToStringContentsParser) {
            $expressionToStringContentsParser($expression, static function ($parameter) {
                Stopwatch::start($parameter);
            });
        });

        Blade::directive('endmeasure', static function ($expression) use ($expressionToStringContentsParser) {
            $expressionToStringContentsParser($expression, static function ($parameter) {
                Stopwatch::pause($parameter);
            });
        });
    }
}
