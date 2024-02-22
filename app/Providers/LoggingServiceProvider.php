<?php

namespace App\Providers;

use App\Logging\StructuredLogging;
use App\Service\AffixGroup\Logging\AffixGroupEaseTierServiceLogging;
use App\Service\AffixGroup\Logging\AffixGroupEaseTierServiceLoggingInterface;
use App\Service\CombatLog\Logging\BaseCombatFilterLogging;
use App\Service\CombatLog\Logging\BaseCombatFilterLoggingInterface;
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
use App\Service\CombatLog\Logging\CreateRouteBodyDungeonRouteBuilderLogging;
use App\Service\CombatLog\Logging\CreateRouteBodyDungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\Logging\CreateRouteDungeonRouteServiceLogging;
use App\Service\CombatLog\Logging\CreateRouteDungeonRouteServiceLoggingInterface;
use App\Service\CombatLog\Logging\DungeonRouteBuilderLogging;
use App\Service\CombatLog\Logging\DungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\Logging\DungeonRouteCombatFilterLogging;
use App\Service\CombatLog\Logging\DungeonRouteCombatFilterLoggingInterface;
use App\Service\CombatLog\Logging\MappingVersionCombatFilterLogging;
use App\Service\CombatLog\Logging\MappingVersionCombatFilterLoggingInterface;
use App\Service\CombatLog\Logging\ResultEventDungeonRouteBuilderLogging;
use App\Service\CombatLog\Logging\ResultEventDungeonRouteBuilderLoggingInterface;
use App\Service\MDT\Logging\MDTMappingImportServiceLogging;
use App\Service\MDT\Logging\MDTMappingImportServiceLoggingInterface;
use App\Service\Patreon\Logging\PatreonApiServiceLogging;
use App\Service\Patreon\Logging\PatreonApiServiceLoggingInterface;
use App\Service\Patreon\Logging\PatreonServiceLogging;
use App\Service\Patreon\Logging\PatreonServiceLoggingInterface;
use App\Service\WowTools\Logging\WowToolsServiceLogging;
use App\Service\WowTools\Logging\WowToolsServiceLoggingInterface;
use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;

class LoggingServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register()
    {
        parent::register();

        // AffixGroup
        $this->app->bind(AffixGroupEaseTierServiceLoggingInterface::class, AffixGroupEaseTierServiceLogging::class);

        // Combat log
        $this->app->bind(CombatLogServiceLoggingInterface::class, CombatLogServiceLogging::class);
        $this->app->bind(CombatLogDungeonRouteServiceLoggingInterface::class, CombatLogDungeonRouteServiceLogging::class);
        $this->app->bind(DungeonRouteBuilderLoggingInterface::class, DungeonRouteBuilderLogging::class);
        $this->app->bind(CreateRouteBodyDungeonRouteBuilderLoggingInterface::class, CreateRouteBodyDungeonRouteBuilderLogging::class);
        $this->app->bind(CreateRouteDungeonRouteServiceLoggingInterface::class, CreateRouteDungeonRouteServiceLogging::class);
        $this->app->bind(ResultEventDungeonRouteBuilderLoggingInterface::class, ResultEventDungeonRouteBuilderLogging::class);
        $this->app->bind(CombatLogSplitServiceLoggingInterface::class, CombatLogSplitServiceLogging::class);
        $this->app->bind(BaseCombatFilterLoggingInterface::class, BaseCombatFilterLogging::class);
        $this->app->bind(CombatLogMappingVersionServiceLoggingInterface::class, CombatLogMappingVersionServiceLogging::class);
        $this->app->bind(MappingVersionCombatFilterLoggingInterface::class, MappingVersionCombatFilterLogging::class);
        $this->app->bind(DungeonRouteCombatFilterLoggingInterface::class, DungeonRouteCombatFilterLogging::class);
        $this->app->bind(CombatLogDataExtractionServiceLoggingInterface::class, CombatLogDataExtractionServiceLogging::class);

        // MDT
        $this->app->bind(MDTMappingImportServiceLoggingInterface::class, MDTMappingImportServiceLogging::class);

        // Patreon
        $this->app->bind(PatreonServiceLoggingInterface::class, PatreonServiceLogging::class);
        $this->app->bind(PatreonApiServiceLoggingInterface::class, PatreonApiServiceLogging::class);

        // Wow Tools
        $this->app->bind(WowToolsServiceLoggingInterface::class, WowToolsServiceLogging::class);
    }

}
