<?php

namespace App\Service\Expansion;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use Illuminate\Support\Collection;

class ExpansionSeasonAffixGroups
{
    /** @var Collection<int, Affix> */
    private readonly Collection $featuredAffixes;

    private readonly ?AffixGroup $currentAffixGroup;

    private readonly ?AffixGroup $nextAffixGroup;

    /** @var Collection<int, AffixGroup> */
    private Collection $allAffixGroups;

    public function __construct(
        ExpansionServiceInterface        $expansionService,
        SeasonAffixGroupServiceInterface $seasonAffixGroupService,
        Expansion                        $expansion,
        GameServerRegion                 $gameServerRegion,
        ExpansionSeason                  $expansionSeason,
    ) {
        $season = $expansionSeason->getSeason();

        $this->currentAffixGroup = $expansionService->getCurrentAffixGroup($expansion, $gameServerRegion);
        $this->nextAffixGroup    = $expansionService->getNextAffixGroup($expansion, $gameServerRegion);

        if ($season !== null) {
            $this->featuredAffixes = $seasonAffixGroupService->getFeaturedAffixes($season);
            $this->allAffixGroups  = $expansionSeason->getSeason()->affixGroups()
                ->with(['affixes:affixes.id,affixes.key,affixes.name,affixes.description'])
                ->get();
        } else {
            $this->featuredAffixes = collect();
            $this->allAffixGroups  = collect();
        }
    }

    /**
     * @return Collection<int, Affix>
     */
    public function getFeaturedAffixes(): Collection
    {
        return $this->featuredAffixes;
    }

    public function getCurrentAffixGroup(): ?AffixGroup
    {
        return $this->currentAffixGroup;
    }

    public function getNextAffixGroup(): ?AffixGroup
    {
        return $this->nextAffixGroup;
    }

    /**
     * @return Collection<int, AffixGroup>
     */
    public function getAllAffixGroups(): Collection
    {
        return $this->allAffixGroups;
    }
}
