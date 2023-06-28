<?php

namespace App\Providers;

use App\Service\CombatLog\Logging\CombatLogDungeonRouteServiceLogging;
use App\Service\CombatLog\Logging\CombatLogDungeonRouteServiceLoggingInterface;
use App\Service\CombatLog\Logging\CombatLogServiceLogging;
use App\Service\CombatLog\Logging\CombatLogServiceLoggingInterface;
use App\Service\CombatLog\Logging\CombatLogSplitServiceLogging;
use App\Service\CombatLog\Logging\CombatLogSplitServiceLoggingInterface;
use App\Service\CombatLog\Logging\CreateRouteBodyDungeonRouteBuilderLogging;
use App\Service\CombatLog\Logging\CreateRouteBodyDungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\Logging\CreateRouteDungeonRouteServiceLogging;
use App\Service\CombatLog\Logging\CreateRouteDungeonRouteServiceLoggingInterface;
use App\Service\CombatLog\Logging\CurrentPullLogging;
use App\Service\CombatLog\Logging\CurrentPullLoggingInterface;
use App\Service\CombatLog\Logging\DungeonRouteBuilderLogging;
use App\Service\CombatLog\Logging\DungeonRouteBuilderLoggingInterface;
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
use Illuminate\Support\ServiceProvider;

class LoggingServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register()
    {
        parent::register();

        // Combat log
        $this->app->bind(CombatLogServiceLoggingInterface::class, CombatLogServiceLogging::class);
        $this->app->bind(CombatLogDungeonRouteServiceLoggingInterface::class, CombatLogDungeonRouteServiceLogging::class);
        $this->app->bind(DungeonRouteBuilderLoggingInterface::class, DungeonRouteBuilderLogging::class);
        $this->app->bind(CreateRouteBodyDungeonRouteBuilderLoggingInterface::class, CreateRouteBodyDungeonRouteBuilderLogging::class);
        $this->app->bind(CreateRouteDungeonRouteServiceLoggingInterface::class, CreateRouteDungeonRouteServiceLogging::class);
        $this->app->bind(ResultEventDungeonRouteBuilderLoggingInterface::class, ResultEventDungeonRouteBuilderLogging::class);
        $this->app->bind(CombatLogSplitServiceLoggingInterface::class, CombatLogSplitServiceLogging::class);
        $this->app->bind(CurrentPullLoggingInterface::class, CurrentPullLogging::class);

        // MDT
        $this->app->bind(MDTMappingImportServiceLoggingInterface::class, MDTMappingImportServiceLogging::class);

        // Patreon
        $this->app->bind(PatreonServiceLoggingInterface::class, PatreonServiceLogging::class);
        $this->app->bind(PatreonApiServiceLoggingInterface::class, PatreonApiServiceLogging::class);

        // Wow Tools
        $this->app->bind(WowToolsServiceLoggingInterface::class, WowToolsServiceLogging::class);
    }

}
