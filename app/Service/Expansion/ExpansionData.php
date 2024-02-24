<?php

namespace App\Service\Expansion;

use App\Models\Expansion;
use App\Models\GameServerRegion;
use Illuminate\Support\Collection;

class ExpansionData
{
    /** @var Collection */
    private Collection $activeDungeons;

    /** @var ExpansionSeason */
    private ExpansionSeason $expansionSeason;

    public function __construct(ExpansionServiceInterface $expansionService, private Expansion $expansion, private GameServerRegion $gameServerRegion)
    {
        $this->activeDungeons  = $this->expansion->dungeons;
        $this->expansionSeason = new ExpansionSeason($expansionService, $this->expansion, $this->gameServerRegion);
    }

    /**
     * @return Expansion
     */
    public function getExpansion(): Expansion
    {
        return $this->expansion;
    }

    /**
     * @return GameServerRegion
     */
    public function getGameServerRegion(): GameServerRegion
    {
        return $this->gameServerRegion;
    }

    /**
     * @return Collection
     */
    public function getActiveDungeons(): Collection
    {
        return $this->activeDungeons;
    }

    /**
     * @return ExpansionSeason
     */
    public function getExpansionSeason(): ExpansionSeason
    {
        return $this->expansionSeason;
    }
}
