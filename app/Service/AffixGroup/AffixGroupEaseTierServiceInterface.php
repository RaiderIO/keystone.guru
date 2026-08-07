<?php

namespace App\Service\AffixGroup;

use App\Models\AffixGroup\AffixGroup;
use App\Models\AffixGroup\AffixGroupEaseTier;
use App\Models\AffixGroup\AffixGroupEaseTierPull;
use App\Models\Dungeon;
use Exception;
use Illuminate\Support\Collection;

interface AffixGroupEaseTierServiceInterface
{
    /**
     * @param array<string, mixed>  $tierList
     * @param array<string, string> $dungeonNameMapping
     */
    public function getTiersHash(array $tierList, array $dungeonNameMapping): string;

    /**
     * @param array<string, mixed> $tierListsResponse
     */
    public function parseTierList(array $tierListsResponse): ?AffixGroupEaseTierPull;

    public function getTierForAffixAndDungeon(AffixGroup $affixGroup, Dungeon $dungeon): ?string;

    /**
     * @param  Collection<int, AffixGroup>         $affixGroups
     * @return Collection<int, AffixGroupEaseTier>
     */
    public function getTiersByAffixGroups(Collection $affixGroups): Collection;

    /**
     * @return Collection<int, AffixGroupEaseTier>
     */
    public function getTiers(): Collection;

    /**
     * Finds the affix group of the current season that has all affixes in the given comma separated list of (English)
     * affix names - the affix group may have more affixes than were given. Prefers the affix group of the current
     * week when it has them; otherwise returns the matching affix group, or null when no affix group of the season
     * has them, or when multiple affix groups with differing affixes do.
     *
     * @throws Exception
     */
    public function getAffixGroupByString(string $easeTierAffixString): ?AffixGroup;
}
