<?php


namespace App\Service\Subcreation;

use App\Models\AffixGroup;
use App\Models\AffixGroupEaseTier;
use App\Models\Dungeon;
use App\Models\SubcreationEaseTierPull;

class AffixGroupEaseTierService implements AffixGroupEaseTierServiceInterface
{
    /**
     * @inheritDoc
     */
    function getTierForAffixAndDungeon(AffixGroup $affixGroup, Dungeon $dungeon): ?string
    {
        $result = null;

        $latestSubcreationEaseTierPull = SubcreationEaseTierPull::latest()->first();

        if ($latestSubcreationEaseTierPull !== null) {
            /** @var AffixGroupEaseTier|null $affixGroupEaseTier */
            $affixGroupEaseTier = $latestSubcreationEaseTierPull->affixgroupeasetiers()
                ->where('affix_group_id', $affixGroup->id)
                ->where('dungeon_id', $dungeon->id)
                ->first();

            if ($affixGroupEaseTier !== null) {
                $result = $affixGroupEaseTier->tier;
            }
        }

        return $result;
    }
}