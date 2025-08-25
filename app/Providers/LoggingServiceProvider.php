<?php

namespace App\Providers;

use App\Exceptions\Logging\HandlerLogging;
use App\Exceptions\Logging\HandlerLoggingInterface;
use App\Http\Middleware\Logging\DebugInfoContextLoggerLogging;
use App\Http\Middleware\Logging\DebugInfoContextLoggerLoggingInterface;
use App\Jobs\Logging\ProcessRouteFloorThumbnailCustomLogging;
use App\Jobs\Logging\ProcessRouteFloorThumbnailCustomLoggingInterface;
use App\Jobs\Logging\ProcessRouteFloorThumbnailLogging;
use App\Jobs\Logging\ProcessRouteFloorThumbnailLoggingInterface;
use App\Service\AffixGroup\Logging\AffixGroupEaseTierServiceLogging;
use App\Service\AffixGroup\Logging\AffixGroupEaseTierServiceLoggingInterface;
use App\Service\Cache\Logging\CacheServiceLogging;
use App\Service\Cache\Logging\CacheServiceLoggingInterface;
use App\Service\ChallengeModeRunData\Logging\ChallengeModeRunDataServiceLogging;
use App\Service\ChallengeModeRunData\Logging\ChallengeModeRunDataServiceLoggingInterface;
use App\Service\Cloudflare\Logging\CloudflareServiceLogging;
use App\Service\Cloudflare\Logging\CloudflareServiceLoggingInterface;
use App\Service\CombatLog\Builders\Logging\CombatLogRouteCombatLogEventsBuilderLogging;
use App\Service\CombatLog\Builders\Logging\CombatLogRouteCombatLogEventsBuilderLoggingInterface;
use App\Service\CombatLog\Builders\Logging\CombatLogRouteCorrectionBuilderLogging;
use App\Service\CombatLog\Builders\Logging\CombatLogRouteCorrectionBuilderLoggingInterface;
use App\Service\CombatLog\Builders\Logging\CombatLogRouteDungeonRouteBuilderLogging;
use App\Service\CombatLog\Builders\Logging\CombatLogRouteDungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\Builders\Logging\DungeonRouteBuilderLogging;
use App\Service\CombatLog\Builders\Logging\DungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\Builders\Logging\ResultEventDungeonRouteBuilderLogging;
use App\Service\CombatLog\Builders\Logging\ResultEventDungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\DataExtractors\Logging\CreateMissingNpcDataExtractorLogging;
use App\Service\CombatLog\DataExtractors\Logging\CreateMissingNpcDataExtractorLoggingInterface;
use App\Service\CombatLog\DataExtractors\Logging\FloorDataExtractorLogging;
use App\Service\CombatLog\DataExtractors\Logging\FloorDataExtractorLoggingInterface;
use App\Service\CombatLog\DataExtractors\Logging\NpcUpdateDataExtractorLogging;
use App\Service\CombatLog\DataExtractors\Logging\NpcUpdateDataExtractorLoggingInterface;
use App\Service\CombatLog\DataExtractors\Logging\SpellDataExtractorLogging;
use App\Service\CombatLog\DataExtractors\Logging\SpellDataExtractorLoggingInterface;
use App\Service\CombatLog\Filters\Logging\BaseCombatFilterLogging;
use App\Service\CombatLog\Filters\Logging\BaseCombatFilterLoggingInterface;
use App\Service\CombatLog\Filters\Logging\DungeonRouteCombatFilterLogging;
use App\Service\CombatLog\Filters\Logging\DungeonRouteCombatFilterLoggingInterface;
use App\Service\CombatLog\Filters\Logging\MappingVersionCombatFilterLogging;
use App\Service\CombatLog\Filters\Logging\MappingVersionCombatFilterLoggingInterface;
use App\Service\CombatLog\Logging\CombatLogDataExtractionServiceLogging;
use App\Service\CombatLog\Logging\CombatLogDataExtractionServiceLoggingInterface;
use App\Service\CombatLog\Logging\CombatLogDungeonRouteServiceLogging;
use App\Service\CombatLog\Logging\CombatLogDungeonRouteServiceLoggingInterface;
use App\Service\CombatLog\Logging\CombatLogMappingVersionServiceLogging;
use App\Service\CombatLog\Logging\CombatLogMappingVersionServiceLoggingInterface;
use App\Service\CombatLog\Logging\CombatLogRouteDungeonRouteServiceLogging;
use App\Service\CombatLog\Logging\CombatLogRouteDungeonRouteServiceLoggingInterface;
use App\Service\CombatLog\Logging\CombatLogServiceLogging;
use App\Service\CombatLog\Logging\CombatLogServiceLoggingInterface;
use App\Service\CombatLog\Logging\CombatLogSplitServiceLogging;
use App\Service\CombatLog\Logging\CombatLogSplitServiceLoggingInterface;
use App\Service\CombatLog\Splitters\Logging\ChallengeModeSplitterLogging;
use App\Service\CombatLog\Splitters\Logging\ChallengeModeSplitterLoggingInterface;
use App\Service\CombatLog\Splitters\Logging\ZoneChangeSplitterLogging;
use App\Service\CombatLog\Splitters\Logging\ZoneChangeSplitterLoggingInterface;
use App\Service\CombatLogEvent\Logging\CombatLogEventServiceLogging;
use App\Service\CombatLogEvent\Logging\CombatLogEventServiceLoggingInterface;
use App\Service\Dungeon\Logging\DungeonServiceLogging;
use App\Service\Dungeon\Logging\DungeonServiceLoggingInterface;
use App\Service\DungeonRoute\Logging\DungeonRouteServiceLogging;
use App\Service\DungeonRoute\Logging\DungeonRouteServiceLoggingInterface;
use App\Service\DungeonRoute\Logging\ThumbnailServiceLogging;
use App\Service\DungeonRoute\Logging\ThumbnailServiceLoggingInterface;
use App\Service\MDT\Logging\MDTImportStringServiceLogging;
use App\Service\MDT\Logging\MDTImportStringServiceLoggingInterface;
use App\Service\MDT\Logging\MDTMappingExportServiceLogging;
use App\Service\MDT\Logging\MDTMappingExportServiceLoggingInterface;
use App\Service\MDT\Logging\MDTMappingImportServiceLogging;
use App\Service\MDT\Logging\MDTMappingImportServiceLoggingInterface;
use App\Service\Patreon\Logging\PatreonApiServiceLogging;
use App\Service\Patreon\Logging\PatreonApiServiceLoggingInterface;
use App\Service\Patreon\Logging\PatreonServiceLogging;
use App\Service\Patreon\Logging\PatreonServiceLoggingInterface;
use App\Service\RaiderIO\Logging\RaiderIOApiServiceLogging;
use App\Service\RaiderIO\Logging\RaiderIOApiServiceLoggingInterface;
use App\Service\Spell\Logging\SpellServiceLogging;
use App\Service\Spell\Logging\SpellServiceLoggingInterface;
use App\Service\StructuredLogging\Logging\StructuredLoggingServiceLogging;
use App\Service\StructuredLogging\Logging\StructuredLoggingServiceLoggingInterface;
use App\Service\Wowhead\Logging\WowheadServiceLogging;
use App\Service\Wowhead\Logging\WowheadServiceLoggingInterface;
use App\Service\Wowhead\Logging\WowheadTranslationServiceLogging;
use App\Service\Wowhead\Logging\WowheadTranslationServiceLoggingInterface;
use App\Service\WowTools\Logging\WowToolsServiceLogging;
use App\Service\WowTools\Logging\WowToolsServiceLoggingInterface;
use Illuminate\Support\ServiceProvider;

class LoggingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        parent::register();

        // Exceptions
        $this->app->bind(HandlerLoggingInterface::class, HandlerLogging::class);

        // Middleware
        $this->app->bind(DebugInfoContextLoggerLoggingInterface::class, DebugInfoContextLoggerLogging::class);

        // AffixGroup
        $this->app->bind(AffixGroupEaseTierServiceLoggingInterface::class, AffixGroupEaseTierServiceLogging::class);

        // Cache
        $this->app->bind(CacheServiceLoggingInterface::class, CacheServiceLogging::class);

        // Challenge Mode Run Data
        $this->app->bind(ChallengeModeRunDataServiceLoggingInterface::class, ChallengeModeRunDataServiceLogging::class);

        // Cloudflare
        $this->app->bind(CloudflareServiceLoggingInterface::class, CloudflareServiceLogging::class);

        // Combat log
        /// Builders
        $this->app->bind(DungeonRouteBuilderLoggingInterface::class, DungeonRouteBuilderLogging::class);
        $this->app->bind(CombatLogRouteDungeonRouteBuilderLoggingInterface::class, CombatLogRouteDungeonRouteBuilderLogging::class);
        $this->app->bind(CombatLogRouteCombatLogEventsBuilderLoggingInterface::class, CombatLogRouteCombatLogEventsBuilderLogging::class);
        $this->app->bind(CombatLogRouteCorrectionBuilderLoggingInterface::class, CombatLogRouteCorrectionBuilderLogging::class);
        $this->app->bind(ResultEventDungeonRouteBuilderLoggingInterface::class, ResultEventDungeonRouteBuilderLogging::class);
        /// DataExtractors
        $this->app->bind(CreateMissingNpcDataExtractorLoggingInterface::class, CreateMissingNpcDataExtractorLogging::class);
        $this->app->bind(FloorDataExtractorLoggingInterface::class, FloorDataExtractorLogging::class);
        $this->app->bind(NpcUpdateDataExtractorLoggingInterface::class, NpcUpdateDataExtractorLogging::class);
        $this->app->bind(SpellDataExtractorLoggingInterface::class, SpellDataExtractorLogging::class);
        /// Filters
        $this->app->bind(BaseCombatFilterLoggingInterface::class, BaseCombatFilterLogging::class);
        $this->app->bind(DungeonRouteCombatFilterLoggingInterface::class, DungeonRouteCombatFilterLogging::class);
        $this->app->bind(MappingVersionCombatFilterLoggingInterface::class, MappingVersionCombatFilterLogging::class);
        /// Splitters
        $this->app->bind(ChallengeModeSplitterLoggingInterface::class, ChallengeModeSplitterLogging::class);
        $this->app->bind(ZoneChangeSplitterLoggingInterface::class, ZoneChangeSplitterLogging::class);
        /// Services
        $this->app->bind(CombatLogServiceLoggingInterface::class, CombatLogServiceLogging::class);
        $this->app->bind(CombatLogDungeonRouteServiceLoggingInterface::class, CombatLogDungeonRouteServiceLogging::class);
        $this->app->bind(CombatLogRouteDungeonRouteServiceLoggingInterface::class, CombatLogRouteDungeonRouteServiceLogging::class);
        $this->app->bind(CombatLogSplitServiceLoggingInterface::class, CombatLogSplitServiceLogging::class);
        $this->app->bind(CombatLogMappingVersionServiceLoggingInterface::class, CombatLogMappingVersionServiceLogging::class);
        $this->app->bind(CombatLogDataExtractionServiceLoggingInterface::class, CombatLogDataExtractionServiceLogging::class);

        // Combat log event
        $this->app->bind(CombatLogEventServiceLoggingInterface::class, CombatLogEventServiceLogging::class);

        // Dungeon
        $this->app->bind(DungeonServiceLoggingInterface::class, DungeonServiceLogging::class);

        // DungeonRoute
        $this->app->bind(DungeonRouteServiceLoggingInterface::class, DungeonRouteServiceLogging::class);
        $this->app->bind(ThumbnailServiceLoggingInterface::class, ThumbnailServiceLogging::class);

        // Jobs
        $this->app->bind(ProcessRouteFloorThumbnailLoggingInterface::class, ProcessRouteFloorThumbnailLogging::class);
        $this->app->bind(ProcessRouteFloorThumbnailCustomLoggingInterface::class, ProcessRouteFloorThumbnailCustomLogging::class);

        // MDT
        $this->app->bind(MDTImportStringServiceLoggingInterface::class, MDTImportStringServiceLogging::class);
        $this->app->bind(MDTMappingExportServiceLoggingInterface::class, MDTMappingExportServiceLogging::class);
        $this->app->bind(MDTMappingImportServiceLoggingInterface::class, MDTMappingImportServiceLogging::class);

        // Patreon
        $this->app->bind(PatreonServiceLoggingInterface::class, PatreonServiceLogging::class);
        $this->app->bind(PatreonApiServiceLoggingInterface::class, PatreonApiServiceLogging::class);

        // RaiderIO
        $this->app->bind(RaiderIOApiServiceLoggingInterface::class, RaiderIOApiServiceLogging::class);

        // Spell
        $this->app->bind(SpellServiceLoggingInterface::class, SpellServiceLogging::class);

        // Structured logging
        $this->app->bind(StructuredLoggingServiceLoggingInterface::class, StructuredLoggingServiceLogging::class);

        // Wowhead
        $this->app->bind(WowheadServiceLoggingInterface::class, WowheadServiceLogging::class);
        $this->app->bind(WowheadTranslationServiceLoggingInterface::class, WowheadTranslationServiceLogging::class);

        // Wow Tools
        $this->app->bind(WowToolsServiceLoggingInterface::class, WowToolsServiceLogging::class);
    }
}
