<?php

namespace App\Service\AffixGroup;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\AffixGroup\AffixGroupEaseTier;
use App\Models\AffixGroup\AffixGroupEaseTierPull;
use App\Models\Dungeon;
use App\Service\AffixGroup\Logging\AffixGroupEaseTierServiceLoggingInterface;
use App\Service\Season\SeasonServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AffixGroupEaseTierService implements AffixGroupEaseTierServiceInterface
{
    public const DUNGEON_NAME_MAPPING = [
        "Dawn of the Infinite: Galakrond's Fall" => "Galakrond's Fall",
        "Dawn of the Infinite: Murozond's Rise"  => "Murozond's Rise",
        'The Everbloom'                          => 'Everbloom',
    ];

    public function __construct(
        private readonly SeasonServiceInterface                    $seasonService,
        private readonly AffixGroupEaseTierServiceLoggingInterface $log
    ) {
    }

    public function getTiersHash(array $tierList, array $dungeonNameMapping): string
    {
        $affixes        = $tierList['encounterTierList']['label'];
        $tiersByDungeon = [];
        foreach ($tierList['encounterTierList']['tierLists'][0]['tiers'] as $tier) {

            foreach ($tier['entries'] as $entry) {
                $tiersByDungeon[$dungeonNameMapping[$entry['name']] ?? $entry['name']] = $tier['tier'];
            }
        }

        // Sort by name
        ksort($tiersByDungeon);

        $implode = [];
        foreach ($tiersByDungeon as $dungeon => $tier) {
            $implode[] = sprintf(
                '%s|%s|%s',
                $dungeon,
                $affixes,
                $tier
            );
        }

        return md5(implode('|', $implode));
    }

    /**
     * {@inheritDoc}
     */
    public function parseTierList(array $tierListsResponse): ?AffixGroupEaseTierPull
    {
        $lastEaseTierPull = AffixGroupEaseTierPull::latest()->first();

        $affixGroupString = $tierListsResponse['encounterTierList']['label'];
        $affixGroup       = $this->getAffixGroupByString($affixGroupString);

        if ($affixGroup === null) {
            $this->log->parseTierListUnknownAffixGroup($affixGroupString);

            return null;
        }

        $result    = null;
        $tiersHash = $this->getTiersHash($tierListsResponse, array_flip(self::DUNGEON_NAME_MAPPING));

        if ($lastEaseTierPull === null ||
            $lastEaseTierPull->affix_group_id !== $affixGroup->id ||
            $lastEaseTierPull->tiers_hash !== $tiersHash) {

            $affixGroupString       = $affixGroup->text;
            $affixGroupEaseTierPull = AffixGroupEaseTierPull::create([
                'affix_group_id'  => $affixGroup->id,
                'tiers_hash'      => $tiersHash,
                'last_updated_at' => Carbon::now()->toDateTimeString(),
            ]);

            $dungeonList = Dungeon::active()->get()->keyBy(static function (Dungeon $dungeon) {
                // Translate the name of the dungeon to English (from a key), and then match it
                $ksgDungeonName = __($dungeon->name, [], 'en_US');

                return self::DUNGEON_NAME_MAPPING[$ksgDungeonName] ?? $ksgDungeonName;
            });

            $affixGroupEaseTiersAttributes = [];
            foreach ($tierListsResponse['encounterTierList']['tierLists'][0]['tiers'] as $tierList) {
                /** @var array{tier: string, entries: array} $tierList */
                try {
                    $tier = $tierList['tier'];
                    $this->log->parseTierListParseTierStart($affixGroupString, $tier, count($tierList['entries']));

                    foreach ($tierList['entries'] as $dungeon) {
                        /** @var array{id: int, name: string, url: string} $dungeon */
                        // If found
                        $dungeonName = $dungeon['name'];
                        $dungeon     = $dungeonList->get($dungeonName);

                        if ($dungeon === null) {
                            $this->log->parseTierListUnknownDungeon($dungeonName);

                            continue;
                        }

                        $affixGroupEaseTiersAttributes[] = [
                            'affix_group_ease_tier_pull_id' => $affixGroupEaseTierPull->id,
                            'affix_group_id'                => $affixGroup->id,
                            'dungeon_id'                    => $dungeon->id,
                            'tier'                          => $tier,
                        ];

                        $this->log->parseTierListSavedDungeonTier($dungeonName, $tier);
                    }
                } finally {
                    $this->log->parseTierListParseTierEnd();
                }
            }

            $affixGroupEaseTierSaveResult = AffixGroupEaseTier::insert($affixGroupEaseTiersAttributes);
            $this->log->parseTierListSave($affixGroupEaseTierSaveResult);

            $result = $affixGroupEaseTierPull;
        } else {
            $this->log->parseTierListDataNotUpdatedYet();
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getTierForAffixAndDungeon(AffixGroup $affixGroup, Dungeon $dungeon): ?string
    {
        $result = null;

        $latestAffixGroupEaseTierPull = AffixGroupEaseTierPull::latest()->first();

        if ($latestAffixGroupEaseTierPull !== null) {
            /** @var AffixGroupEaseTier|null $affixGroupEaseTier */
            $affixGroupEaseTier = $latestAffixGroupEaseTierPull->affixGroupEaseTiers()
                ->where('affix_group_id', $affixGroup->id)
                ->where('dungeon_id', $dungeon->id)
                ->first();

            $result = $affixGroupEaseTier?->tier;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getTiersByAffixGroups(Collection $affixGroups): Collection
    {
        $result = collect();

        $latestAffixGroupEaseTierPull = AffixGroupEaseTierPull::latest()->first();
        if ($latestAffixGroupEaseTierPull !== null) {
            /** @var AffixGroupEaseTier|null $affixGroupEaseTier */
            $result = $latestAffixGroupEaseTierPull->affixGroupEaseTiers()
                ->whereIn('affix_group_id', $affixGroups->pluck('id')->toArray())
                ->get()
                ->groupBy('affix_group_id');
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getTiers(): Collection
    {
        $result = collect();

        $latestAffixGroupEaseTierPull = AffixGroupEaseTierPull::latest()->first();
        if ($latestAffixGroupEaseTierPull !== null) {
            $result = $latestAffixGroupEaseTierPull->affixGroupEaseTiers;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getAffixGroupByString(string $affixString): ?AffixGroup
    {
        $result = null;

        $affixList                = Affix::all();
        $currentSeason            = $this->seasonService->getCurrentSeason();
        $currentSeasonAffixGroups = $currentSeason->affixgroups;

        $affixes = collect(explode(', ', $affixString));

        // Filter out properties that don't have the correct amount of affixes
        if ($affixes->count() === 3 + (int)($currentSeason->seasonal_affix_id !== null)) {
            // Check if there's any affixes in the list that we cannot find in our own database
            $invalidAffixes = $affixes->filter(static fn(string $affixName) => $affixList->filter(static fn(Affix $affix) => __($affix->name, [], 'en_US') === $affixName)->isEmpty());

            // No invalid affixes found, great!
            if ($invalidAffixes->isEmpty()) {
                // Now we must find affix groups that correspond to the affix list
                foreach ($currentSeasonAffixGroups as $affixGroup) {

                    // Loop over the affixes of the affix group and empty the list
                    $notFoundAffixes = $affixGroup->affixes->filter(static fn(Affix $affix) => $affixes->search($affix->key) === false);

                    // If we have found the match, we're done
                    if ($notFoundAffixes->isEmpty()) {
                        $result = $affixGroup;
                        break;
                    }
                }
            } else {
                $this->log->getAffixGroupByStringUnknownAffixes($invalidAffixes->join(', '));
            }
        }

        return $result;
    }
}
