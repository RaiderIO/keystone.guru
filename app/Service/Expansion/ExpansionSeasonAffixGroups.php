<?php

namespace App\Service\Expansion;

use App\Models\AffixGroup\AffixGroup;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use Illuminate\Support\Collection;

class ExpansionSeasonAffixGroups
{
    /** @var Collection */
    private Collection $currentAffixGroups;

    /** @var Collection */
    private Collection $nextAffixGroups;

    /** @var Collection */
    private Collection $allAffixGroups;


    /**
     * @param ExpansionServiceInterface $expansionService
     * @param Expansion $expansion
     * @param ExpansionSeason $expansionSeason
     */
    public function __construct(ExpansionServiceInterface $expansionService, Expansion $expansion, ExpansionSeason $expansionSeason)
    {
        $allRegions = GameServerRegion::all();

        $this->currentAffixGroups = $allRegions->mapWithKeys(function (GameServerRegion $region) use ($expansionService, $expansion) {
            return [$region->short => $expansionService->getCurrentAffixGroup($expansion, $region)];
        });

        $this->nextAffixGroups = $allRegions->mapWithKeys(function (GameServerRegion $region) use ($expansionService, $expansion) {
            return [$region->short => $expansionService->getNextAffixGroup($expansion, $region)];
        });

        $this->allAffixGroups = $expansionSeason->getSeason()->affixgroups()
            ->with(['affixes:affixes.id,affixes.key,affixes.name,affixes.description'])
            ->get();
    }

    /**
     * @param GameServerRegion $gameServerRegion
     * @return AffixGroup|null
     */
    public function getCurrentAffixGroup(GameServerRegion $gameServerRegion): ?AffixGroup
    {
        return $this->currentAffixGroups->get($gameServerRegion->short);
    }

    /**
     * @param GameServerRegion $gameServerRegion
     * @return AffixGroup|null
     */
    public function getNextAffixGroup(GameServerRegion $gameServerRegion): ?AffixGroup
    {
        return $this->nextAffixGroups->get($gameServerRegion->short);
    }

    /**
     * @return Collection
     */
    public function getCurrentAffixGroups(): Collection
    {
        return $this->currentAffixGroups;
    }

    /**
     * @return Collection
     */
    public function getNextAffixGroups(): Collection
    {
        return $this->nextAffixGroups;
    }

    /**
     * @return Collection
     */
    public function getAllAffixGroups(): Collection
    {
        return $this->allAffixGroups;
    }
}
