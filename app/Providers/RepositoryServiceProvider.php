<?php

namespace App\Providers;

use App\Repositories\Database\AffixGroup\AffixGroupRepository;
use App\Repositories\Database\DungeonRoute\DungeonRouteAffixGroupRepository;
use App\Repositories\Database\DungeonRoute\DungeonRouteRepository;
use App\Repositories\Database\KillZone\KillZoneEnemyRepository;
use App\Repositories\Database\KillZone\KillZoneRepository;
use App\Repositories\Database\KillZone\KillZoneSpellRepository;
use App\Repositories\Database\SpellRepository;
use App\Repositories\Interfaces\AffixGroup\AffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneSpellRepositoryInterface;
use App\Repositories\Interfaces\SpellRepositoryInterface;
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
