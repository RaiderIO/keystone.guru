<?php


namespace App\Service\EaseTier;

use App\Models\AffixGroup\AffixGroup;
use App\Models\AffixGroup\AffixGroupEaseTier;
use App\Models\Dungeon;
use App\Models\SubcreationEaseTierPull;
use Illuminate\Support\Collection;

class AffixGroupEaseTierService implements AffixGroupEaseTierServiceInterface
{

    /**
     * @inheritDoc
     */
    public function getTierForAffixAndDungeon(AffixGroup $affixGroup, Dungeon $dungeon): ?string
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

    /**
     * @inheritDoc
     */
    public function getTiersByAffixGroups(Collection $affixGroups): Collection
    {
        $result = collect();

        $latestSubcreationEaseTierPull = SubcreationEaseTierPull::latest()->first();
        if ($latestSubcreationEaseTierPull !== null) {
            /** @var AffixGroupEaseTier|null $affixGroupEaseTier */
            $result = $latestSubcreationEaseTierPull->affixgroupeasetiers()
                ->whereIn('affix_group_id', $affixGroups->pluck('id')->toArray())
                ->get()
                ->groupBy('affix_group_id');
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getTiers(): Collection
    {
        $result = collect();

        $latestSubcreationEaseTierPull = SubcreationEaseTierPull::latest()->first();
        if ($latestSubcreationEaseTierPull !== null) {
            $result = $latestSubcreationEaseTierPull->affixgroupeasetiers;
        }

        return $result;
    }

}
