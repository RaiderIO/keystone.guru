<?php

namespace App\Providers;

use App\Http\Middleware\Logging\DebugInfoContextLoggerLogging;
use App\Http\Middleware\Logging\DebugInfoContextLoggerLoggingInterface;
use App\Service\AffixGroup\Logging\AffixGroupEaseTierServiceLogging;
use App\Service\AffixGroup\Logging\AffixGroupEaseTierServiceLoggingInterface;
use App\Service\Cache\Logging\CacheServiceLogging;
use App\Service\Cache\Logging\CacheServiceLoggingInterface;
use App\Service\ChallengeModeRunData\Logging\ChallengeModeRunDataServiceLogging;
use App\Service\ChallengeModeRunData\Logging\ChallengeModeRunDataServiceLoggingInterface;
use App\Service\CombatLog\Builders\Logging\CreateRouteBodyCombatLogEventsBuilderLogging;
use App\Service\CombatLog\Builders\Logging\CreateRouteBodyCombatLogEventsBuilderLoggingInterface;
use App\Service\CombatLog\Builders\Logging\CreateRouteBodyCorrectionBuilderLogging;
use App\Service\CombatLog\Builders\Logging\CreateRouteBodyCorrectionBuilderLoggingInterface;
use App\Service\CombatLog\Builders\Logging\CreateRouteBodyDungeonRouteBuilderLogging;
use App\Service\CombatLog\Builders\Logging\CreateRouteBodyDungeonRouteBuilderLoggingInterface;
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
use App\Service\CombatLog\Logging\CombatLogServiceLogging;
use App\Service\CombatLog\Logging\CombatLogServiceLoggingInterface;
use App\Service\CombatLog\Logging\CombatLogSplitServiceLogging;
use App\Service\CombatLog\Logging\CombatLogSplitServiceLoggingInterface;
use App\Service\CombatLog\Logging\CreateRouteDungeonRouteServiceLogging;
use App\Service\CombatLog\Logging\CreateRouteDungeonRouteServiceLoggingInterface;
use App\Service\CombatLog\Splitters\Logging\ChallengeModeSplitterLogging;
use App\Service\CombatLog\Splitters\Logging\ChallengeModeSplitterLoggingInterface;
use App\Service\CombatLog\Splitters\Logging\ZoneChangeSplitterLogging;
use App\Service\CombatLog\Splitters\Logging\ZoneChangeSplitterLoggingInterface;
use App\Service\CombatLogEvent\Logging\CombatLogEventServiceLogging;
use App\Service\CombatLogEvent\Logging\CombatLogEventServiceLoggingInterface;
use App\Service\Dungeon\Logging\DungeonServiceLogging;
use App\Service\Dungeon\Logging\DungeonServiceLoggingInterface;
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
use App\Service\Spell\Logging\SpellServiceLogging;
use App\Service\Spell\Logging\SpellServiceLoggingInterface;
use App\Service\StructuredLogging\Logging\StructuredLoggingServiceLogging;
use App\Service\StructuredLogging\Logging\StructuredLoggingServiceLoggingInterface;
use App\Service\Wowhead\Logging\WowheadServiceLogging;
use App\Service\Wowhead\Logging\WowheadServiceLoggingInterface;
use App\Service\WowTools\Logging\WowToolsServiceLogging;
use App\Service\WowTools\Logging\WowToolsServiceLoggingInterface;
use Illuminate\Support\ServiceProvider;

class LoggingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        parent::register();

        // Middleware
        $this->app->bind(DebugInfoContextLoggerLoggingInterface::class, DebugInfoContextLoggerLogging::class);

        // AffixGroup
        $this->app->bind(AffixGroupEaseTierServiceLoggingInterface::class, AffixGroupEaseTierServiceLogging::class);

        // Cache
        $this->app->bind(CacheServiceLoggingInterface::class, CacheServiceLogging::class);

        // Challenge Mode Run Data
        $this->app->bind(ChallengeModeRunDataServiceLoggingInterface::class, ChallengeModeRunDataServiceLogging::class);

        // Combat log
        /// Builders
        $this->app->bind(DungeonRouteBuilderLoggingInterface::class, DungeonRouteBuilderLogging::class);
        $this->app->bind(CreateRouteBodyDungeonRouteBuilderLoggingInterface::class, CreateRouteBodyDungeonRouteBuilderLogging::class);
        $this->app->bind(CreateRouteBodyCombatLogEventsBuilderLoggingInterface::class, CreateRouteBodyCombatLogEventsBuilderLogging::class);
        $this->app->bind(CreateRouteBodyCorrectionBuilderLoggingInterface::class, CreateRouteBodyCorrectionBuilderLogging::class);
        $this->app->bind(ResultEventDungeonRouteBuilderLoggingInterface::class, ResultEventDungeonRouteBuilderLogging::class);
        /// DataExtractors
        $this->app->bind(CreateMissingNpcDataExtractorLoggingInterface::class, CreateMissingNpcDataExtractorLogging::class);
        $this->app->bind(FloorDataExtractorLoggingInterface::class, FloorDataExtractorLogging::class);
        $this->app->bind(NpcUpdateDataExtractorLoggingInterface::class, NpcUpdateDataExtractorLogging::class);
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
        $this->app->bind(CreateRouteDungeonRouteServiceLoggingInterface::class, CreateRouteDungeonRouteServiceLogging::class);
        $this->app->bind(CombatLogSplitServiceLoggingInterface::class, CombatLogSplitServiceLogging::class);
        $this->app->bind(CombatLogMappingVersionServiceLoggingInterface::class, CombatLogMappingVersionServiceLogging::class);
        $this->app->bind(CombatLogDataExtractionServiceLoggingInterface::class, CombatLogDataExtractionServiceLogging::class);

        // Combat log event
        $this->app->bind(CombatLogEventServiceLoggingInterface::class, CombatLogEventServiceLogging::class);

        // Dungeon
        $this->app->bind(DungeonServiceLoggingInterface::class, DungeonServiceLogging::class);

        // MDT
        $this->app->bind(MDTImportStringServiceLoggingInterface::class, MDTImportStringServiceLogging::class);
        $this->app->bind(MDTMappingExportServiceLoggingInterface::class, MDTMappingExportServiceLogging::class);
        $this->app->bind(MDTMappingImportServiceLoggingInterface::class, MDTMappingImportServiceLogging::class);

        // Patreon
        $this->app->bind(PatreonServiceLoggingInterface::class, PatreonServiceLogging::class);
        $this->app->bind(PatreonApiServiceLoggingInterface::class, PatreonApiServiceLogging::class);

        // Spell
        $this->app->bind(SpellServiceLoggingInterface::class, SpellServiceLogging::class);

        // Structured logging
        $this->app->bind(StructuredLoggingServiceLoggingInterface::class, StructuredLoggingServiceLogging::class);

        // Wowhead
        $this->app->bind(WowheadServiceLoggingInterface::class, WowheadServiceLogging::class);

        // Wow Tools
        $this->app->bind(WowToolsServiceLoggingInterface::class, WowToolsServiceLogging::class);
    }
}
