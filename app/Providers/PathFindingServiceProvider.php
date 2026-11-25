<?php

namespace App\Providers;

use App\Service\KillZonePath\KillZonePathService;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use App\Service\PathFinding\PathFindingService;
use App\Service\PathFinding\PathFindingServiceInterface;
use App\Service\WalkGraph\PackDistanceService;
use App\Service\WalkGraph\PackDistanceServiceInterface;
use App\Service\WalkGraph\PackReachabilityService;
use App\Service\WalkGraph\PackReachabilityServiceInterface;
use App\Service\WalkGraph\WalkGraphService;
use App\Service\WalkGraph\WalkGraphServiceInterface;
use Illuminate\Support\ServiceProvider;
use Override;

class PathFindingServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->bind(PathFindingServiceInterface::class, PathFindingService::class);
        $this->app->bind(KillZonePathServiceInterface::class, KillZonePathService::class);
        $this->app->scoped(WalkGraphServiceInterface::class, WalkGraphService::class);
        $this->app->bind(PackDistanceServiceInterface::class, PackDistanceService::class);
        $this->app->bind(PackReachabilityServiceInterface::class, PackReachabilityService::class);
    }
}
