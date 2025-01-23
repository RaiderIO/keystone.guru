<?php

namespace App\Service\Expansion;

use App\Models\Expansion;
use App\Models\GameServerRegion;
use Illuminate\Support\Collection;

class ExpansionData
{
    private readonly Collection $activeDungeons;

    private readonly ExpansionSeason $expansionSeason;

    public function __construct(ExpansionServiceInterface $expansionService, private readonly Expansion $expansion, private readonly GameServerRegion $gameServerRegion)
    {
        $this->activeDungeons  = $this->expansion->dungeonsAndRaids;
        $this->expansionSeason = new ExpansionSeason($expansionService, $this->expansion, $this->gameServerRegion);
    }

    public function getExpansion(): Expansion
    {
        return $this->expansion;
    }

    public function getGameServerRegion(): GameServerRegion
    {
        return $this->gameServerRegion;
    }

    public function getActiveDungeons(): Collection
    {
        return $this->activeDungeons;
    }

    public function getExpansionSeason(): ExpansionSeason
    {
        return $this->expansionSeason;
    }
}
