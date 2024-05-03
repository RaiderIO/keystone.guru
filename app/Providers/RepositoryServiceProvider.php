<?php

namespace App\Providers;

use App\Repositories\AffixGroup\AffixGroupRepository;
use App\Repositories\AffixGroup\AffixGroupRepositoryInterface;
use App\Repositories\DungeonRoute\DungeonRouteAffixGroupRepository;
use App\Repositories\DungeonRoute\DungeonRouteAffixGroupRepositoryInterface;
use App\Repositories\DungeonRoute\DungeonRouteRepository;
use App\Repositories\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\KillZone\KillZoneEnemyRepository;
use App\Repositories\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\KillZone\KillZoneRepository;
use App\Repositories\KillZone\KillZoneRepositoryInterface;
use App\Repositories\KillZone\KillZoneSpellRepository;
use App\Repositories\KillZone\KillZoneSpellRepositoryInterface;
use App\Repositories\SpellRepository;
use App\Repositories\SpellRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        parent::register();

        // AffixGroup
        $this->app->bind(AffixGroupRepositoryInterface::class, AffixGroupRepository::class);

        // DungeonRoute
        $this->app->bind(DungeonRouteAffixGroupRepositoryInterface::class, DungeonRouteAffixGroupRepository::class);
        $this->app->bind(DungeonRouteRepositoryInterface::class, DungeonRouteRepository::class);

        // KillZone
        $this->app->bind(KillZoneEnemyRepositoryInterface::class, KillZoneEnemyRepository::class);
        $this->app->bind(KillZoneRepositoryInterface::class, KillZoneRepository::class);
        $this->app->bind(KillZoneSpellRepositoryInterface::class, KillZoneSpellRepository::class);

        // Root
        $this->app->bind(SpellRepositoryInterface::class, SpellRepository::class);
    }
}
