<?php

namespace App\Providers;

use App\Service\KillZonePath\KillZonePathService;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use App\Service\PathFinding\PathFindingService;
use App\Service\PathFinding\PathFindingServiceInterface;
use Illuminate\Support\ServiceProvider;

class PathFindingServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->bind(PathFindingServiceInterface::class, PathFindingService::class);
        $this->app->bind(KillZonePathServiceInterface::class, KillZonePathService::class);
    }
}
