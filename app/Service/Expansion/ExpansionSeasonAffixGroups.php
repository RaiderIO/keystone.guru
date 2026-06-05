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
    /** @var Collection<Affix> */
    private readonly Collection $featuredAffixes;

    private readonly ?AffixGroup $currentAffixGroup;

    private readonly ?AffixGroup $nextAffixGroup;

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
     * @return Collection<Affix>
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
     * @return Collection<AffixGroup>
     */
    public function getAllAffixGroups(): Collection
    {
        return $this->allAffixGroups;
    }
}
