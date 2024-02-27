<?php

namespace App\Providers;

use App\Service\Controller\Api\V1\APIDungeonRouteControllerService;
use App\Service\Controller\Api\V1\APIDungeonRouteControllerServiceInterface;
use MarvinLabs\DiscordLogger\ServiceProvider;

class ControllerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // API
        $this->app->bind(APIDungeonRouteControllerServiceInterface::class, APIDungeonRouteControllerService::class);

        // Site
    }
}
