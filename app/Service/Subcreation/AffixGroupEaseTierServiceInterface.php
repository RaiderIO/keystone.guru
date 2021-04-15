<?php

namespace App\Service\Subcreation;

use App\Models\AffixGroup;
use App\Models\Dungeon;

interface AffixGroupEaseTierServiceInterface
{
    /**
     * @param AffixGroup $affixGroup
     * @param Dungeon $dungeon
     * @return string|null
     */
    function getTierForAffixAndDungeon(AffixGroup $affixGroup, Dungeon $dungeon): ?string;
}