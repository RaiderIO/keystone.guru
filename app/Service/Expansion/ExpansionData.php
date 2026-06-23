<?php

namespace App\Service\Expansion;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use Illuminate\Support\Collection;

class ExpansionData
{
    /**
     * @var Collection<int, Dungeon>
     */
    private readonly Collection $activeDungeons;

    private readonly ExpansionSeason $expansionSeason;

    public function __construct(
        ExpansionServiceInterface         $expansionService,
        SeasonAffixGroupServiceInterface  $seasonAffixGroupService,
        private readonly Expansion        $expansion,
        private readonly GameServerRegion $gameServerRegion,
    ) {
        $this->activeDungeons  = $this->expansion->dungeonsAndRaids;
        $this->expansionSeason = new ExpansionSeason($expansionService, $seasonAffixGroupService, $this->expansion, $this->gameServerRegion);
    }

    public function getExpansion(): Expansion
    {
        return $this->expansion;
    }

    public function getGameServerRegion(): GameServerRegion
    {
        return $this->gameServerRegion;
    }

    /**
     * @return Collection<int, Dungeon>
     */
    public function getActiveDungeons(): Collection
    {
        return $this->activeDungeons;
    }

    public function getExpansionSeason(): ExpansionSeason
    {
        return $this->expansionSeason;
    }
}
