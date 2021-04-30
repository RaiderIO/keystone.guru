<?php

namespace App\Service\Subcreation;

use App\Models\AffixGroup;
use App\Models\AffixGroupEaseTier;
use App\Models\Dungeon;
use Illuminate\Support\Collection;

interface AffixGroupEaseTierServiceInterface
{
    /**
     * @param AffixGroup $affixGroup
     * @param Dungeon $dungeon
     * @return string|null
     */
    function getTierForAffixAndDungeon(AffixGroup $affixGroup, Dungeon $dungeon): ?string;

    /**
     * @param Collection $affixGroups
     * @return Collection|AffixGroupEaseTier[]
     */
    function getTiersByAffixGroups(Collection $affixGroups): Collection;
}