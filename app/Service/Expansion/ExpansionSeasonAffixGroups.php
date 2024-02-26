<?php

namespace App\Service\Expansion;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use Illuminate\Support\Collection;

class ExpansionSeasonAffixGroups
{
    /** @var Collection|Affix[] */
    private readonly Collection $featuredAffixes;

    private readonly ?AffixGroup $currentAffixGroup;

    private readonly ?AffixGroup $nextAffixGroup;

    private Collection $allAffixGroups;

    public function __construct(ExpansionServiceInterface $expansionService, Expansion $expansion, GameServerRegion $gameServerRegion, ExpansionSeason $expansionSeason)
    {
        $this->featuredAffixes = $expansionSeason->getSeason()?->getFeaturedAffixes() ?? collect();
        $this->currentAffixGroup = $expansionService->getCurrentAffixGroup($expansion, $gameServerRegion);
        $this->nextAffixGroup = $expansionService->getNextAffixGroup($expansion, $gameServerRegion);

        if ($expansionSeason->getSeason() !== null) {
            $this->allAffixGroups = $expansionSeason->getSeason()->affixgroups()
                ->with(['affixes:affixes.id,affixes.key,affixes.name,affixes.description'])
                ->get();
        } else {
            $this->allAffixGroups = collect();
        }
    }

    /**
     * @return Collection|Affix[]
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
     * @return Collection|AffixGroup[]
     */
    public function getAllAffixGroups(): Collection
    {
        return $this->allAffixGroups;
    }
}
