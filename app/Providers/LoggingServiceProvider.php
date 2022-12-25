<?php

namespace App\Providers;

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

        $this->app->bind(PatreonServiceLoggingInterface::class, PatreonServiceLogging::class);
        $this->app->bind(PatreonApiServiceLoggingInterface::class, PatreonApiServiceLogging::class);
        $this->app->bind(WowToolsServiceLoggingInterface::class, WowToolsServiceLogging::class);
    }

}
