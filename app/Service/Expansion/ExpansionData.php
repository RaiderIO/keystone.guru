<?php

namespace App\Service\Expansion;

use App\Models\Expansion;
use App\Models\GameServerRegion;
use Illuminate\Support\Collection;

class ExpansionData
{
    /** @var Expansion */
    private Expansion $expansion;

    /** @var Collection */
    private Collection $activeDungeons;

    /** @var ExpansionSeason */
    private ExpansionSeason $expansionSeason;

    private GameServerRegion $gameServerRegion;

    /**
     * @param ExpansionServiceInterface $expansionService
     * @param Expansion                 $expansion
     * @param GameServerRegion          $gameServerRegion
     */
    public function __construct(ExpansionServiceInterface $expansionService, Expansion $expansion, GameServerRegion $gameServerRegion)
    {
        $this->expansion        = $expansion;
        $this->gameServerRegion = $gameServerRegion;
        $this->activeDungeons   = $expansion->dungeons;
        $this->expansionSeason  = new ExpansionSeason($expansionService, $expansion, $gameServerRegion);
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
