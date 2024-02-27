<?php

namespace App\Service\AffixGroup;

use App\Models\AffixGroup\AffixGroup;
use App\Models\AffixGroup\AffixGroupEaseTier;
use App\Models\AffixGroup\AffixGroupEaseTierPull;
use App\Models\Dungeon;
use Illuminate\Support\Collection;

interface AffixGroupEaseTierServiceInterface
{
    public function getTiersHash(array $tierList, array $dungeonNameMapping): string;

    public function parseTierList(array $tierListsResponse): ?AffixGroupEaseTierPull;

    public function getTierForAffixAndDungeon(AffixGroup $affixGroup, Dungeon $dungeon): ?string;

    /**
     * @return Collection|AffixGroupEaseTier[]
     */
    public function getTiersByAffixGroups(Collection $affixGroups): Collection;

    public function getTiers(): Collection;

    public function getAffixGroupByString(string $affixString): ?AffixGroup;
}
