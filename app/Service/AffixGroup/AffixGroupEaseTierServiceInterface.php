<?php

namespace App\Service\AffixGroup;

use App\Models\AffixGroup\AffixGroup;
use App\Models\AffixGroup\AffixGroupEaseTier;
use App\Models\AffixGroup\AffixGroupEaseTierPull;
use App\Models\Dungeon;
use Illuminate\Support\Collection;

interface AffixGroupEaseTierServiceInterface
{
    /**
     * @param array $tierList
     * @param array $dungeonNameMapping
     * @return string
     */
    public function getTiersHash(array $tierList, array $dungeonNameMapping): string;

    /**
     * @param array $tierListsResponse
     * @return AffixGroupEaseTierPull|null
     */
    public function parseTierList(array $tierListsResponse): ?AffixGroupEaseTierPull;

    /**
     * @param AffixGroup $affixGroup
     * @param Dungeon    $dungeon
     * @return string|null
     */
    public function getTierForAffixAndDungeon(AffixGroup $affixGroup, Dungeon $dungeon): ?string;

    /**
     * @param Collection $affixGroups
     * @return Collection|AffixGroupEaseTier[]
     */
    public function getTiersByAffixGroups(Collection $affixGroups): Collection;

    /**
     * @return Collection
     */
    public function getTiers(): Collection;

    /**
     * @param string $affixString
     * @return AffixGroup|null
     */
    public function getAffixGroupByString(string $affixString): ?AffixGroup;
}
