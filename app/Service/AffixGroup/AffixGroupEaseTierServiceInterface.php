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
     * @return string
     */
    public function getTiersHash(array $tierList, array $dungeonNameMapping): string;

    /**
     * @return AffixGroupEaseTierPull|null
     */
    public function parseTierList(array $tierListsResponse): ?AffixGroupEaseTierPull;

    /**
     * @return string|null
     */
    public function getTierForAffixAndDungeon(AffixGroup $affixGroup, Dungeon $dungeon): ?string;

    /**
     * @return Collection|AffixGroupEaseTier[]
     */
    public function getTiersByAffixGroups(Collection $affixGroups): Collection;

    /**
     * @return Collection
     */
    public function getTiers(): Collection;

    /**
     * @return AffixGroup|null
     */
    public function getAffixGroupByString(string $affixString): ?AffixGroup;
}
