<?php

namespace App\Providers;

use App\Http\View\Composers\AdminDungeonMappingVersionsComposer;
use App\Http\View\Composers\AdminMessageBannerComposer;
use App\Http\View\Composers\AdminNpcHealthEditComposer;
use App\Http\View\Composers\AdminSpellEditComposer;
use App\Http\View\Composers\AffixesComposer;
use App\Http\View\Composers\AppLayoutComposer;
use App\Http\View\Composers\CompositionComposer;
use App\Http\View\Composers\CreateRouteFormComposer;
use App\Http\View\Composers\DiscoverAffixGroupComposer;
use App\Http\View\Composers\DiscoverSearchComposer;
use App\Http\View\Composers\DungeonDifficultySelectComposer;
use App\Http\View\Composers\DungeonGridDiscoverComposer;
use App\Http\View\Composers\DungeonGridTabsComposer;
use App\Http\View\Composers\DungeonSelectComposer;
use App\Http\View\Composers\DungeonStartSelectComposer;
use App\Http\View\Composers\EmbedComposer;
use App\Http\View\Composers\GameVersionsNavComposer;
use App\Http\View\Composers\GlobalComposer;
use App\Http\View\Composers\HeaderComposer;
use App\Http\View\Composers\HeatmapSearchComposer;
use App\Http\View\Composers\MapComposer;
use App\Http\View\Composers\MappingVersionComposer;
use App\Http\View\Composers\OAuthRegisterFormComposer;
use App\Http\View\Composers\ProfileEditComposer;
use App\Http\View\Composers\ProfileNewRouteStyleComposer;
use App\Http\View\Composers\PullsComposer;
use App\Http\View\Composers\PullsWorkbenchComposer;
use App\Http\View\Composers\RollbarComposer;
use App\Http\View\Composers\RouteAttributesComposer;
use App\Http\View\Composers\RouteCoverageAffixGroupComposer;
use App\Http\View\Composers\RoutePublishComposer;
use App\Http\View\Composers\RouteTierComposer;
use App\Http\View\Composers\SimulateComposer;
use App\Http\View\Composers\SimulateOptionsComposer;
use App\Http\View\Composers\TeamSelectComposer;
use App\Logic\Utils\Counter;
use App\Logic\Utils\Stopwatch;
use App\Service\AdProvider\AdProviderService;
use App\Service\AdProvider\AdProviderServiceInterface;
use App\Service\AffixGroup\AffixGroupEaseTierService;
use App\Service\AffixGroup\AffixGroupEaseTierServiceInterface;
use App\Service\AffixGroup\ArchonApiService;
use App\Service\AffixGroup\ArchonApiServiceInterface;
use App\Service\Cache\CacheService;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Cache\DevCacheService;
use App\Service\Cache\Redis\PHPRedisService;
use App\Service\Cache\Redis\RedisServiceInterface;
use App\Service\ChallengeModeRunData\ChallengeModeRunDataService;
use App\Service\ChallengeModeRunData\ChallengeModeRunDataServiceInterface;
use App\Service\Cloudflare\CloudflareService;
use App\Service\Cloudflare\CloudflareServiceInterface;
use App\Service\CombatLog\CombatLogDataExtractionService;
use App\Service\CombatLog\CombatLogDataExtractionServiceInterface;
use App\Service\CombatLog\CombatLogMappingVersionService;
use App\Service\CombatLog\CombatLogMappingVersionServiceInterface;
use App\Service\CombatLog\CombatLogParsingCriteriaService;
use App\Service\CombatLog\CombatLogParsingCriteriaServiceInterface;
use App\Service\CombatLog\CombatLogRouteDungeonRouteService;
use App\Service\CombatLog\CombatLogRouteDungeonRouteServiceInterface;
use App\Service\CombatLog\CombatLogRouteEnemyFailureService;
use App\Service\CombatLog\CombatLogRouteEnemyFailureServiceInterface;
use App\Service\CombatLog\CombatLogService;
use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\CombatLogSplitService;
use App\Service\CombatLog\CombatLogSplitServiceInterface;
use App\Service\CombatLog\ResultEventDungeonRouteService;
use App\Service\CombatLog\ResultEventDungeonRouteServiceInterface;
use App\Service\CombatLogEvent\CombatLogEventService;
use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\Compendium\NpcCompendiumService;
use App\Service\Compendium\NpcCompendiumServiceInterface;
use App\Service\Compendium\SpellCompendiumService;
use App\Service\Compendium\SpellCompendiumServiceInterface;
use App\Service\Cookies\CookieService;
use App\Service\Cookies\CookieServiceInterface;
use App\Service\Coordinates\CoordinatesService;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Discord\DiscordApiService;
use App\Service\Discord\DiscordApiServiceInterface;
use App\Service\Dungeon\DungeonService;
use App\Service\Dungeon\DungeonServiceInterface;
use App\Service\DungeonRoute\CoverageService;
use App\Service\DungeonRoute\CoverageServiceInterface;
use App\Service\DungeonRoute\DiscoverService;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\DungeonRoute\DungeonRouteSaveService;
use App\Service\DungeonRoute\DungeonRouteSaveServiceInterface;
use App\Service\DungeonRoute\DungeonRouteSearchService;
use App\Service\DungeonRoute\DungeonRouteSearchServiceInterface;
use App\Service\DungeonRoute\DungeonRouteService;
use App\Service\DungeonRoute\DungeonRouteServiceInterface;
use App\Service\DungeonRoute\MapDrawingService;
use App\Service\DungeonRoute\MapDrawingServiceInterface;
use App\Service\DungeonRoute\ThumbnailService;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use App\Service\Expansion\ExpansionService;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\GameVersion\GameVersionService;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\Image\ImageService;
use App\Service\Image\ImageServiceInterface;
use App\Service\LiveSession\OverpulledEnemyService;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use App\Service\MapContext\MapContextService;
use App\Service\MapContext\MapContextServiceInterface;
use App\Service\Mapping\MappingExportService;
use App\Service\Mapping\MappingExportServiceInterface;
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
use App\Service\MDT\MDTMappingVersionService;
use App\Service\MDT\MDTMappingVersionServiceInterface;
use App\Service\MessageBanner\MessageBannerService;
use App\Service\MessageBanner\MessageBannerServiceInterface;
use App\Service\Metric\MetricService;
use App\Service\Metric\MetricServiceInterface;
use App\Service\Npc\NpcService;
use App\Service\Npc\NpcServiceInterface;
use App\Service\Patreon\PatreonApiService;
use App\Service\Patreon\PatreonApiServiceInterface;
use App\Service\Patreon\PatreonService;
use App\Service\Patreon\PatreonServiceInterface;
use App\Service\RaiderIO\RaiderIOApiService;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use App\Service\RaiderIO\RaiderIOKeystoneGuruApiService;
use App\Service\ReadOnlyMode\ReadOnlyModeService;
use App\Service\ReadOnlyMode\ReadOnlyModeServiceInterface;
use App\Service\Reverb\ReverbHttpApiService;
use App\Service\Reverb\ReverbHttpApiServiceInterface;
use App\Service\Season\SeasonAffixGroupService;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonService;
use App\Service\Season\SeasonServiceInterface;
use App\Service\SimulationCraft\RaidEventsService;
use App\Service\SimulationCraft\RaidEventsServiceInterface;
use App\Service\Spell\SpellService;
use App\Service\Spell\SpellServiceInterface;
use App\Service\StructuredLogging\StructuredLoggingService;
use App\Service\StructuredLogging\StructuredLoggingServiceInterface;
use App\Service\TimewalkingEvent\TimewalkingEventService;
use App\Service\TimewalkingEvent\TimewalkingEventServiceInterface;
use App\Service\User\UserService;
use App\Service\User\UserServiceInterface;
use App\Service\View\RequestViewContext;
use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewService;
use App\Service\View\ViewServiceInterface;
use App\Service\Wowhead\WowheadService;
use App\Service\Wowhead\WowheadServiceInterface;
use App\Service\Wowhead\WowheadTranslationService;
use App\Service\Wowhead\WowheadTranslationServiceInterface;
use App\Service\WowTools\WowToolsService;
use App\Service\WowTools\WowToolsServiceInterface;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Override;

class KeystoneGuruServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    #[Override]
    public function register(): void
    {
        // External communication - no dependencies
        $this->app->bind(DiscordApiServiceInterface::class, DiscordApiService::class);
        $this->app->bind(ArchonApiServiceInterface::class, ArchonApiService::class);
        $this->app->bind(PatreonApiServiceInterface::class, PatreonApiService::class);
        $this->app->bind(WowToolsServiceInterface::class, WowToolsService::class);
        $this->app->bind(AdProviderServiceInterface::class, AdProviderService::class);
        $this->app->bind(WowheadServiceInterface::class, WowheadService::class);
        $this->app->bind(WowheadTranslationServiceInterface::class, WowheadTranslationService::class);
        if (
            app()->runningUnitTests()
            || app()->environment('local')
        ) {
            $this->app->bind(RaiderIOApiServiceInterface::class, RaiderIOKeystoneGuruApiService::class);
        } else {
            $this->app->bind(RaiderIOApiServiceInterface::class, RaiderIOApiService::class);
        }
        $this->app->bind(CloudflareServiceInterface::class, CloudflareService::class);

        // Bind the interface to the actual service
        $this->app->bind(ReverbHttpApiServiceInterface::class, ReverbHttpApiService::class);

        // Internals
        $this->app->bind(CoordinatesServiceInterface::class, CoordinatesService::class);
        $this->app->bind(ThumbnailServiceInterface::class, ThumbnailService::class);
        $this->app->bind(PatreonServiceInterface::class, PatreonService::class);
        $this->app->bind(MetricServiceInterface::class, MetricService::class);
        $this->app->bind(CombatLogServiceInterface::class, CombatLogService::class);
        $this->app->bind(CombatLogSplitServiceInterface::class, CombatLogSplitService::class);
        $this->app->bind(CombatLogMappingVersionServiceInterface::class, CombatLogMappingVersionService::class);
        $this->app->bind(CombatLogParsingCriteriaServiceInterface::class, CombatLogParsingCriteriaService::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(StructuredLoggingServiceInterface::class, StructuredLoggingService::class);
        $this->app->bind(SpellServiceInterface::class, SpellService::class);
        $this->app->bind(ChallengeModeRunDataServiceInterface::class, ChallengeModeRunDataService::class);
        $this->app->bind(CombatLogEventServiceInterface::class, CombatLogEventService::class);
        $this->app->bind(DungeonServiceInterface::class, DungeonService::class);
        $this->app->bind(CookieServiceInterface::class, CookieService::class);
        $this->app->bind(DungeonRouteSaveServiceInterface::class, DungeonRouteSaveService::class);
        $this->app->bind(DungeonRouteServiceInterface::class, DungeonRouteService::class);
        $this->app->bind(DungeonRouteSearchServiceInterface::class, DungeonRouteSearchService::class);
        $this->app->bind(ImageServiceInterface::class, ImageService::class);
        $this->app->bind(MessageBannerServiceInterface::class, MessageBannerService::class);
        $this->app->bind(MapDrawingServiceInterface::class, MapDrawingService::class);

        // Depends on CookieService
        $this->app->bind(GameVersionServiceInterface::class, GameVersionService::class);

        // Depends on CoordinatesService
        $this->app->bind(RaidEventsServiceInterface::class, RaidEventsService::class);

        // Model helpers
        if (in_array(config('app.env'), [
            'local',
            'testing',
        ])) {
            $this->app->bind(CacheServiceInterface::class, DevCacheService::class);
            $this->app->bind(DiscoverServiceInterface::class, DiscoverService::class);
        } else {
            $this->app->bind(CacheServiceInterface::class, CacheService::class);
            $this->app->bind(DiscoverServiceInterface::class, DiscoverService::class);
        }
        $this->app->bind(RedisServiceInterface::class, PHPRedisService::class);

        $this->app->bind(ExpansionServiceInterface::class, ExpansionService::class);
        $this->app->bind(NpcCompendiumServiceInterface::class, NpcCompendiumService::class);
        $this->app->bind(SpellCompendiumServiceInterface::class, SpellCompendiumService::class);
        $this->app->bind(NpcServiceInterface::class, NpcService::class);

        // Depends on CacheService
        $this->app->bind(ReadOnlyModeServiceInterface::class, ReadOnlyModeService::class);

        // Depends on CacheService, CoordinatesService
        $this->app->bind(MDTMappingVersionServiceInterface::class, MDTMappingVersionService::class);
        $this->app->bind(MDTMappingExportServiceInterface::class, MDTMappingExportService::class);
        $this->app->bind(MDTMappingImportServiceInterface::class, MDTMappingImportService::class);

        // Depends on ExpansionService
        $this->app->bind(SeasonServiceInterface::class, SeasonService::class);
        $this->app->bind(OverpulledEnemyServiceInterface::class, OverpulledEnemyService::class);

        // Depends on SeasonService, TimewalkingEventService
        $this->app->bind(SeasonAffixGroupServiceInterface::class, SeasonAffixGroupService::class);
        $this->app->bind(MappingServiceInterface::class, MappingService::class);
        $this->app->bind(MappingExportServiceInterface::class, MappingExportService::class);
        $this->app->bind(CoverageServiceInterface::class, CoverageService::class);

        // Depends on SeasonService
        $this->app->bind(AffixGroupEaseTierServiceInterface::class, AffixGroupEaseTierService::class);

        // Depends on CacheService, CoordinatesService, OverpulledEnemyService, SeasonService
        $this->app->bind(MapContextServiceInterface::class, MapContextService::class);

        // Depends on SeasonService, CacheService, CoordinatesService
        $this->app->bind(TimewalkingEventServiceInterface::class, TimewalkingEventService::class);
        $this->app->bind(MDTImportStringServiceInterface::class, MDTImportStringService::class);
        $this->app->bind(MDTExportStringServiceInterface::class, MDTExportStringService::class);

        // Depends on CombatLogService, SeasonService, CoordinatesService
        $this->app->bind(CombatLogRouteDungeonRouteServiceInterface::class, CombatLogRouteDungeonRouteService::class);
        $this->app->bind(CombatLogRouteEnemyFailureServiceInterface::class, CombatLogRouteEnemyFailureService::class);
        $this->app->bind(ResultEventDungeonRouteServiceInterface::class, ResultEventDungeonRouteService::class);

        // Depends on all of the above - pretty much
        $this->app->bind(ViewServiceInterface::class, ViewService::class);

        // Request-scoped so per-request memoized values are reset between requests under Octane
        $this->app->scoped(RequestViewContextInterface::class, RequestViewContext::class);

        // Depends on CombatLogService, SeasonService, WowheadService
        $this->app->bind(CombatLogDataExtractionServiceInterface::class, CombatLogDataExtractionService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(ViewServiceInterface $viewService): void
    {
        // There really is nothing here that's useful for console apps - migrations may fail trying to do the below anyway
        if (!app()->runningUnitTests()) {
            if (app()->runningInConsole()) {
                return;
            }

            if (!$viewService->shouldLoadViewVariables(request()->getPathInfo())) {
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

        // Register a dedicated composer class per view; each pulls only the data its view needs,
        // lazily resolved from the granular cached getters instead of one eager multi-MB blob.
        view()->composer('*', GlobalComposer::class);

        // Main view
        view()->composer([
            'layouts.app',
            'layouts.sitepage',
            'layouts.map',
        ], AppLayoutComposer::class);

        view()->composer('common.maps.map', MapComposer::class);

        view()->composer('common.layout.nav.gameversions', GameVersionsNavComposer::class);

        view()->composer('common.layout.header', HeaderComposer::class);

        view()->composer([
            'misc.embedexplore',
            'misc.embedheatmap',
        ], EmbedComposer::class);

        view()->composer([
            'dungeonroute.discover.category',
            'dungeonroute.discover.dungeon.category',
            'dungeonroute.discover.season.category',
            'misc.affixes',
            'dungeonroute.discover.discover',
            'dungeonroute.discover.dungeon.overview',
        ], DiscoverAffixGroupComposer::class);

        // Dungeon grid view
        view()->composer('dungeonroute.discover.search', DiscoverSearchComposer::class);

        view()->composer('common.dungeonroute.create.dungeondifficultyselect', DungeonDifficultySelectComposer::class);

        view()->composer('common.dungeonroute.create.dungeonstartselect', DungeonStartSelectComposer::class);

        view()->composer([
            'common.forms.oauth',
            'common.forms.register',
        ], OAuthRegisterFormComposer::class);

        view()->composer([
            'common.forms.createroute',
            'common.forms.createroutetemporary',
        ], CreateRouteFormComposer::class);

        // Displaying affixes
        view()->composer('common.group.affixes', AffixesComposer::class);

        // Displaying a composition
        view()->composer('common.group.composition', CompositionComposer::class);

        // Dungeon grid display
        view()->composer('common.dungeon.gridtabs', DungeonGridTabsComposer::class);

        view()->composer('common.dungeon.griddiscover', DungeonGridDiscoverComposer::class);

        // Dungeon selector
        view()->composer('common.dungeon.select', DungeonSelectComposer::class);

        // Dungeonroute attributes selector, Dungeonroute table
        view()->composer([
            'common.dungeonroute.attributes',
            'common.dungeonroute.table',
        ], RouteAttributesComposer::class);

        view()->composer('common.dungeonroute.publish', RoutePublishComposer::class);

        view()->composer('common.dungeonroute.tier', RouteTierComposer::class);

        view()->composer('common.dungeonroute.coverage.affixgroup', RouteCoverageAffixGroupComposer::class);

        // Maps
        view()->composer('common.maps.controls.heatmapsearch', HeatmapSearchComposer::class);

        view()->composer('common.maps.controls.pulls', PullsComposer::class);

        view()->composer('common.maps.controls.pullsworkbench', PullsWorkbenchComposer::class);

        // Admin
        view()->composer('admin.dungeon.mappingversions', AdminDungeonMappingVersionsComposer::class);

        view()->composer('admin.npchealth.edit', AdminNpcHealthEditComposer::class);

        view()->composer('admin.spell.edit', AdminSpellEditComposer::class);

        view()->composer('admin.tools.messagebanner.set', AdminMessageBannerComposer::class);

        // Team selector
        view()->composer('common.team.select', TeamSelectComposer::class);

        // Simulation
        view()->composer('common.modal.simulate', SimulateComposer::class);

        view()->composer('common.modal.simulateoptions.default', SimulateOptionsComposer::class);

        view()->composer([
            'common.modal.mappingversion',
            'common.mappingversion.select',
        ], MappingVersionComposer::class);

        // Thirdparty
        view()->composer('common.thirdparty.rollbar.rollbar', RollbarComposer::class);

        // Profile pages
        view()->composer('profile.edit', ProfileEditComposer::class);

        view()->composer([
            'profile.overview',
            'common.dungeonroute.coverage.affixgroup',
        ], ProfileNewRouteStyleComposer::class);

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
